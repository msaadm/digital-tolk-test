<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    const JOB_STATUS_ACCEPT = 1;
    const JOB_STATUS_CANCEL = 2;
    const JOB_STATUS_END = 3;
    const JOB_STATUS_CUSTOMER_NOT_CALL = 4;

    const JOB_NOTIFICATION_PUSH = 1;
    const JOB_NOTIFICATION_SMS = 2;

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        // If Normal User Requests, Send back only his Jobs
        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif (in_array($request->__authenticatedUser->user_type, [env('ADMIN_ROLE_ID'), env('SUPERADMIN_ROLE_ID')])) {
            // If Admin/Super Admin Requests, Send back all Jobs
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($job_id, Request $request)
    {
        $data = $request->all();

        $response = $this->repository->updateJob($job_id, $request->__authenticatedUser, array_except($data, ['_token', 'submit']));

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $user_id = $request->get('user_id');

        if ($request->__authenticatedUser->id == $request->get('user_id') ||
            in_array($request->__authenticatedUser->user_type, [env('ADMIN_ROLE_ID'), env('SUPERADMIN_ROLE_ID')])) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);

            return response($response);
        }

        return null;
    }

    /**
     * @param $job_id
     * @param $status_id
     * @return mixed
     */

    protected function updateJobStatus(Request $request, $job_id, $status_id)
    {
        $user = $user = $request->__authenticatedUser;

        if($status_id == BookingController::JOB_STATUS_ACCEPT) {
            $response = $this->repository->acceptJob($job_id, $user);
        } elseif($status_id == BookingController::JOB_STATUS_ACCEPT) {
            $response = $this->repository->cancelJobAjax($job_id, $user);
        } elseif($status_id == BookingController::JOB_STATUS_ACCEPT) {
            $response = $this->repository->endJob($job_id);
        } elseif($status_id == BookingController::JOB_STATUS_ACCEPT) {
            $response = $this->repository->customerNotCall($job_id);
        } else {
            $response = null;
        }
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $job_id = $request->get('job_id');

        return $this->updateJobStatus($request, $job_id, BookingController::JOB_STATUS_ACCEPT);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $job_id = $request->get('job_id');

        return $this->updateJobStatus($request, $job_id, BookingController::JOB_STATUS_CANCEL);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $job_id = $request->get('job_id');

        return $this->updateJobStatus($request, $job_id, BookingController::JOB_STATUS_END);
    }

    public function customerNotCall(Request $request)
    {
        $job_id = $request->get('job_id');

        return $this->updateJobStatus($request, $job_id, BookingController::JOB_STATUS_CUSTOMER_NOT_CALL);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = $data['distance'] ?? "";
        $time = $data['time'] ?? "";
        $jobid = $data['jobid'] ?? "";
        $session = $data['session_time'] ?? "";
        $admincomment = $data['admincomment'] ?? "";

        $manually_handled = $data['manually_handled'] == 'true' ? "yes" : "no";
        $by_admin = $data['by_admin'] == 'true' ? "yes" : "no";

        if ($data['flagged'] == 'true') {
            if ($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        $respone = null;

        if ($time || $distance) {
            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array(
                'distance' => $distance,
                'time' => $time
            ));

            $response = 'Record updated!';
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(array(
                'admin_comments' => $admincomment,
                'flagged' => $flagged,
                'session_time' => $session,
                'manually_handled' => $manually_handled,
                'by_admin' => $by_admin
            ));

            $response = 'Record updated!';
        }

        return response($respone);
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    /**
     * Sends Notification(Push or SMS) to Translator
     * @param Request $request
     * @param $job_id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */

    public function resendNotifications(Request $request, $job_id, $notification_id)
    {
        $job = $this->repository->find($job_id);
        try {

        if($notification_id == BookingController::JOB_NOTIFICATION_PUSH) {
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendNotificationTranslator($job, $job_data, '*');
            $response = response(['success' => 'Push sent']);
        } elseif($notification_id == BookingController::JOB_NOTIFICATION_SMS){
            $this->repository->sendSMSNotificationToTranslator($job);
            $response = response(['success' => 'SMS sent']);
        } else {
            $response = response(['failed' => 'Notification not sent']);
        }

        } catch (\Exception $e) {
            $response = response(['success' => $e->getMessage()]);
        }

        return $response;
    }

}
