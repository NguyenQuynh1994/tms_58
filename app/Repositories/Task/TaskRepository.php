<?php

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\User;
use App\Models\UserSubject;
use App\Models\UserTask;
use App\Repositories\BaseRepository;
use Exception;
use Auth;
use App\Events\SubjectActivity;
use Request;
use App\Models\Subject;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $task)
    {
        $this->model = $task;
    }

    public function updateTaskStatus($input, $ids)
    {
        if (is_array($ids)) {
            foreach ($ids as $key => $id) {
                $data = UserTask::where('id', $id)->update($input[$key]);
            }
            $userTask = UserTask::where(['id' => $ids[0], 'user_id' => Auth::user()->id])->first();
        } else {
            $data = UserTask::where('id', $ids)->update($input);
            $userTask = UserTask::where(['id' => $ids, 'user_id' => Auth::user()->id])->first();
        }

        if (!$data) {
            throw new Exception(trans('general/message.update_error'));
        }

        $task = $this->model->where('id', $userTask['task_id'])->first();
        $taskIds = $this->model->where('subject_id', $task['subject_id'])->lists('id');
        $completedTasks = UserTask::whereIn('task_id', $taskIds)->where(['user_id' => Auth::user()->id, 'status' => 1])->get();

        if (count($taskIds) == count($completedTasks)) {
            $updatedData = [
                'end_date' => date("Y-m-d"),
                'status' => config('common.subject.status.finish')
            ];
            UserSubject::where(['subject_id' => $task['subject_id'], 'user_id' => Auth::user()->id])->update($updatedData);
            $eventData = [
                'type' => config('common.activity.type.finish_subject'),
                'subject_id' => $task['subject_id'],
                'subject' => Subject::find($task['subject_id'])->name
            ];
            event(new SubjectActivity($eventData));
        }

        return $data;
    }

    public function find($id = null)
    {
        $data = $this->model->find($id);

        if (!$data) {
            throw new Exception("general/message.item_not_exist");
        }

        return $data;
    }

    public function store($inputs)
    {
        $data = $this->model->insert($inputs);

        if (!$data) {
            throw new Exception(trans('general/message.create_error'));
        }

        return $data;
    }
}
