<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\FormSubmission;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\Contracts\FormSubmissionRepository;

class FormSubmissionController extends BackendController
{
    /**
     * @var FormSubmissionRepository
     */
    protected $formSubmissions;

    /**
     * Create a new controller instance.
     *
     * @param FormSubmissionRepository $formSubmissions
     */
    public function __construct(FormSubmissionRepository $formSubmissions)
    {
        $this->formSubmissions = $formSubmissions;
    }

    public function getFormSubmissionCounter()
    {
        return $this->formSubmissions->query()->count();
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var \Yajra\DataTables\EloquentDataTable $query */
            $query = DataTables::of($this->formSubmissions->select([
                'id',
                'type',
                'data',
                'created_at',
                'updated_at',
            ]));

            return $query->editColumn('type', function (FormSubmission $formSubmission) {
                return __("forms.{$formSubmission->type}.display_name");
            })->addColumn('actions', function (FormSubmission $formSubmission) {
                return $this->formSubmissions->getActionButtons($formSubmission);
            })->editColumn('created_at', function (FormSubmission $formSubmission) use ($request) {
                return $formSubmission->created_at->setTimezone($request->user()->timezone);
            })->editColumn('updated_at', function (FormSubmission $formSubmission) use ($request) {
                return $formSubmission->updated_at->setTimezone($request->user()->timezone);
            })
                ->rawColumns(['actions'])
                ->make(true);
        }
    }

    /**
     * @param FormSubmission $form_submission
     *
     * @return FormSubmission
     */
    public function show(FormSubmission $form_submission)
    {
        return $form_submission;
    }

    /**
     * @param FormSubmission $form_submission
     * @param Request        $request
     *
     * @return mixed
     */
    public function destroy(FormSubmission $form_submission, Request $request)
    {
        $this->authorize('delete form_submissions');

        $this->formSubmissions->destroy($form_submission);

        return $this->RedirectResponse($request, __('alerts.backend.form_submissions.deleted'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public function batchAction(Request $request)
    {
        $action = $request->get('action');
        $ids = $request->get('ids');

        switch ($action) {
            case 'destroy':
                $this->authorize('delete form_submissions');

                $this->formSubmissions->batchDestroy($ids);

                return $this->RedirectResponse($request, __('alerts.backend.form_submissions.bulk_destroyed'));
                break;
        }

        return $this->RedirectResponse($request, __('alerts.backend.actions.invalid'), 'error');
    }
}
