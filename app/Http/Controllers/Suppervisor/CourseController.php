<?php

namespace App\Http\Controllers\Suppervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Requests\CreateCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Repositories\Course\CourseRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Subject\SubjectRepositoryInterface;
use App\Repositories\CourseSubject\CourseSubjectRepositoryInterface;
use App\Http\Requests\AddSuppervisorRequest;
use Exception;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Models\CourseSubject;
use Collection;

class CourseController extends Controller
{
    private $courseRepository;
    private $userRepository;
    private $subjectRepository;
    private $courseSubjectRepository;

    public function __construct(
        CourseRepositoryInterface $courseRepository,
        UserRepositoryInterface $userRepository,
        SubjectRepositoryInterface $subjectRepository,
        CourseSubjectRepositoryInterface $courseSubjectRepository
    )
    {
        $this->courseRepository = $courseRepository;
        $this->userRepository = $userRepository;
        $this->subjectRepository = $subjectRepository;
        $this->courseSubjectRepository = $courseSubjectRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $courses = $this->courseRepository->index('courses');

        return view('suppervisor.course.index', $courses);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $subjects = $this->subjectRepository->listSubject();

        return view('suppervisor.course.create', [
            'subjects' => $subjects,
            'message' => (!count($subjects)) ? trans('general/message.items_not_exist') : ''
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(CreateCourseRequest $request)
    {
        try {
            $course = [];

            if (request()->has('ids', 'name', 'description')) {
                $subjectIds = request()->get('ids');
                $course = [
                    'name' => request()->get('name'),
                    'description' => request()->get('description')
                ];
            }

            $data = $this->courseRepository->store($course);
            $courseId = $data->id;
            $courseSubject = $this->courseSubjectRepository->create($subjectIds, $courseId);
            session()->flash('message', trans('general/message.create_successfully'));

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = $this->courseRepository->find($id);

        return view('suppervisor.course.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $course = $this->courseRepository->find($id);
            session()->flash('courseId', $id);
            $subjectIds =  $this->courseRepository->subjectIds($id);

            $subjects = $this->subjectRepository->listSubject();

            return view('suppervisor.course.edit', [
                'course' => $course,
                'subjectIds' => $subjectIds,
                'subjects' => $subjects
            ]);
        } catch (Exception $e) {

            return redirect()->route('admin.courses.index')->withError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCourseRequest $request, $id)
    {
        try {
            $course = [];
            if (request()->has('ids', 'name', 'description', 'subjectIds')) {
                $course = [
                    'name' => request()->get('name'),
                    'description' => request()->get('description')
                ];
                $newSubjectIds = request()->get('ids');
                $oldSubejctIds = request()->get('subjectIds');
            }

            $course = $this->courseRepository->updateCourse($id, $newSubjectIds, $course);
            session()->flash('message', trans('general/message.update_successfully'));

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function addSuppervisor($id)
    {
        $suppervisor = $this->userRepository->listSupervisor($id);
        $course = $this->courseRepository->find($id);

        return view('suppervisor.course.add_supper', compact('course', 'suppervisor'));
    }

    public function createSupper(AddSuppervisorRequest $request)
    {
        $newSuppervisor = [
            'user_id' => $request->user_id,
            'course_id' => $request->course_id
        ];

        try {
            $data = $this->courseRepository->addSuppervisor($newSuppervisor);

            return redirect()->route('admin.courses.index')->with([
                'message' => trans('settings.create_success')
            ]);
        } catch (Exception $e) {
            return redirect()->route('admin.courses.index')->withError($e->getMessage());
        }
    }

    public function traineeProgress($id)
    {
        try {
            $data = $this->courseRepository->traineeProgress($id);

            return view('suppervisor.course.trainee_progress', $data);
        } catch (Exception $e) {
            return redirect()->route('admin.courses.index')->withError($e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (request()->has('ids')) {
            $ids = request()->get('ids');
        }

        try {
            $data = $this->courseRepository->delete($ids);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return response()->json(['success' => false]);
        }

        session()->flash('message', trans('general/message.delete_successfully'));

        return response()->json(['success' => true]);
    }
}
