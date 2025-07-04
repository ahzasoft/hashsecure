<?php

namespace Modules\Essentials\Http\Controllers;

use App\User;
use App\Utils\ModuleUtil;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;
use Modules\Essentials\Utils\EssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $essentialsUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $attendance = EssentialsAttendance::where('essentials_attendances.business_id', $business_id)
                            ->join('users as u', 'u.id', '=', 'essentials_attendances.user_id')
                            ->leftjoin('essentials_shifts as es', 'es.id', '=', 'essentials_attendances.essentials_shift_id')
                            ->select([
                                'essentials_attendances.id',
                                'clock_in_time',
                                'clock_out_time',
                                'clock_in_note',
                                'clock_out_note',
                                'ip_address',
                                DB::raw('DATE(clock_in_time) as date'),
                                DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                                'es.name as shift_name'
                            ]);

            if (!empty(request()->input('employee_id'))) {
                $attendance->where('essentials_attendances.user_id', request()->input('employee_id'));
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $attendance->whereDate('clock_in_time', '>=', $start)
                            ->whereDate('clock_in_time', '<=', $end);
            }

            if (!$is_admin) {
                $attendance->where('essentials_attendances.user_id', auth()->user()->id);
            }

            return Datatables::of($attendance)
                    ->addColumn(
                        'action',
                        '<button data-href="{{action(\'\Modules\Essentials\Http\Controllers\AttendanceController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container="#edit_attendance_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        <button class="btn btn-xs btn-danger delete-attendance" data-href="{{action(\'\Modules\Essentials\Http\Controllers\AttendanceController@destroy\', [$id])}}"><i class="fa fa-trash"></i> @lang("messages.delete")</button>
                        '
                    )
                    ->addColumn('clock_in_clock_out', function ($row) {
                        $html = $this->moduleUtil->format_date($row->clock_in_time, true);
                        if (!empty($row->clock_out_time)) {
                            $html .= ' - ' . $this->moduleUtil->format_date($row->clock_out_time, true);
                        }

                        return $html;
                    })
                    ->editColumn('work_duration', function ($row) {
                        $clock_in = \Carbon::parse($row->clock_in_time);
                        if (!empty($row->clock_out_time)) {
                            $clock_out = \Carbon::parse($row->clock_out_time);
                        } else {
                            $clock_out = \Carbon::now();
                        }

                        $html = $clock_in->diffForHumans($clock_out, true, true, 2);

                        return $html;
                    })
                    ->editColumn('date', '{{@format_date($date)}}')
                    ->rawColumns(['action', 'clock_in_clock_out', 'work_duration'])
                    ->filterColumn('user', function ($query, $keyword) {
                        $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                    })
                    ->make(true);
        }

        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];

        $is_employee_allowed = !empty($settings['allow_users_for_attendance']) ? true : false;
        $clock_in = EssentialsAttendance::where('business_id', $business_id)
                                ->where('user_id', auth()->user()->id)
                                ->whereNull('clock_out_time')
                                ->first();
        $employees = [];
        if ($is_admin) {
            $employees = User::forDropdown($business_id, false);
        }

        $days = $this->moduleUtil->getDays();

        return view('essentials::attendance.index')
            ->with(compact('is_admin', 'is_employee_allowed', 'clock_in', 'employees', 'days'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')) && !$is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $employees = User::forDropdown($business_id, false);

        return view('essentials::attendance.create')->with(compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $attendance = $request->input('attendance');
            $ip_address = $this->moduleUtil->getUserIpAddr();
            if (!empty($attendance)) {
                foreach ($attendance as $user_id => $value) {
                    $data = [
                        'business_id' => $business_id,
                        'user_id' => $user_id
                    ];

                    if (!empty($value['clock_in_time'])) {
                        $data['clock_in_time'] = $this->moduleUtil->uf_date($value['clock_in_time'], true);
                    }
                    if (!empty($value['id'])) {
                        $data['id'] = $value['id'];
                    }
                    EssentialsAttendance::updateOrCreate(
                        $data,
                        [
                            'clock_out_time' => !empty($value['clock_out_time']) ? $this->moduleUtil->uf_date($value['clock_out_time'], true) : null,
                            'ip_address' => !empty($value['ip_address']) ? $value['ip_address'] : $ip_address,
                            'clock_in_note' => $value['clock_in_note'],
                            'clock_out_note' => $value['clock_out_note'],
                            'essentials_shift_id' => $value['essentials_shift_id']
                        ]
                    );
                }
            }

            $output = ['success' => true,
                            'msg' => __("lang_v1.added_success")
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        $attendance = EssentialsAttendance::where('business_id', $business_id)
                                    ->with(['employee'])
                                    ->find($id);

        return view('essentials::attendance.edit')->with(compact('attendance'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['clock_in_time', 'clock_out_time', 'ip_address', 'clock_in_note', 'clock_out_note']);

            $input['clock_in_time'] = $this->moduleUtil->uf_date($input['clock_in_time'], true);
            $input['clock_out_time'] = !empty($input['clock_out_time']) ? $this->moduleUtil->uf_date($input['clock_out_time'], true) : null;

            $attendance = EssentialsAttendance::where('business_id', $business_id)
                                            ->where('id', $id)
                                            ->update($input);
            $output = ['success' => true,
                            'msg' => __("lang_v1.updated_success")
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                EssentialsAttendance::where('business_id', $business_id)->where('id', $id)->delete();

                $output = ['success' => true,
                            'msg' => __("lang_v1.deleted_success")
                        ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Clock in / Clock out the logged in user.
     * @return array
     */
    public function clockInClockOut(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if employees allowed to add their own attendance
        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];
        if (empty($settings['allow_users_for_attendance'])) {
            return ['success' => false,
                        'msg' => __("essentials::lang.not_allowed")
                    ];
        }

        try {
           //type" => "clock_in"
            $type = $request->input('type');

            if ($type == 'clock_in') {
                //Check user can clock in
                $shift = $this->essentialsUtil->checkUserShift(auth()->user()->id, $settings);

                if (empty($shift)) {
                    $output = ['success' => false,
                            'msg' => __("essentials::lang.shift_not_allocated"),
                            'type' => $type
                        ];
                    return $output;
                }
                //Check if already clocked in
                $count = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', auth()->user()->id)
                                        ->whereNull('clock_out_time')
                                        ->count();
                if ($count == 0) {
                    $data = [
                        'business_id' => $business_id,
                        'user_id' => auth()->user()->id,
                        'clock_in_time' => \Carbon::now(),
                        'clock_in_note' => $request->input('clock_in_note'),
                        'ip_address' => $this->moduleUtil->getUserIpAddr(),
                        'essentials_shift_id' => $shift
                    ];

                    EssentialsAttendance::create($data);

                    $output = ['success' => true,
                            'msg' => __("essentials::lang.clock_in_success"),
                            'type' => 'clock_in'
                        ];
                } else {
                    $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong"),
                            'type' => $type
                        ];
                }
            } elseif ($type == 'clock_out') {
                //Get clock in
                $clock_in = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', auth()->user()->id)
                                        ->whereNull('clock_out_time')
                                        ->first();

                $can_clockout = $this->essentialsUtil->canClockOut($clock_in, $settings);
                if (!$can_clockout) {
                    $output = ['success' => false,
                            'msg' => __("essentials::lang.shift_not_over"),
                            'type' => $type
                        ];
                    return $output;
                }

                if (!empty($clock_in)) {
                    $clock_in->clock_out_time = \Carbon::now();
                    $clock_in->clock_out_note = $request->input('clock_out_note');
                    $clock_in->save();

                    $output = ['success' => true,
                            'msg' => __("essentials::lang.clock_out_success"),
                            'type' => 'clock_out'
                        ];
                } else {
                    $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong"),
                            'type' => $type
                        ];
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong"),
                            'type' => $type
                        ];
        }

        return $output;
    }

    /**
     * Function to get attendance summary of a user
     * @return Response
     */
    public function getUserAttendanceSummary()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id = $is_admin ? request()->input('user_id') : auth()->user()->id;

        if (empty($user_id)) {
            return '';
        }

        $start_date = !empty(request()->start_date) ? request()->start_date : null;
        $end_date =  !empty(request()->end_date) ? request()->end_date : null;

        $total_work_duration = $this->essentialsUtil->getTotalWorkDuration('hour', $user_id, $business_id, $start_date, $end_date);

        return $total_work_duration;
    }

    /**
     * Function to validate clock in and clock out time
     * @return string
     */
    public function validateClockInClockOut(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_ids = explode(',', $request->input('user_ids'));
        $clock_in_time = $request->input('clock_in_time');
        $clock_out_time = $request->input('clock_out_time');
        $attendance_id = $request->input('attendance_id');

        $is_valid = 'true';
        if (!empty($user_ids)) {

            //Check if clock in time falls under any existing attendance range
            $is_clock_in_exists = false;
            if (!empty($clock_in_time)) {
                $clock_in_time = $this->essentialsUtil->uf_date($clock_in_time, true);

                $is_clock_in_exists = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('id', '!=', $attendance_id)
                                        ->whereIn('user_id', $user_ids)
                                        ->where('clock_in_time', '<', $clock_in_time)
                                        ->where('clock_out_time', '>', $clock_in_time)
                                        ->exists();
            }

            //Check if clock out time falls under any existing attendance range
            $is_clock_out_exists = false;
            if (!empty($clock_out_time)) {
                $clock_out_time = $this->essentialsUtil->uf_date($clock_out_time, true);

                $is_clock_out_exists = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('id', '!=', $attendance_id)
                                        ->whereIn('user_id', $user_ids)
                                        ->where('clock_in_time', '<', $clock_out_time)
                                        ->where('clock_out_time', '>', $clock_out_time)
                                        ->exists();
            }

            if ($is_clock_in_exists || $is_clock_out_exists) {
                $is_valid = 'false';
            }
        }

        return $is_valid;
    }

    /**
     * Get attendance summary by shift
     */
    public function getAttendanceByShift()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        $date = $this->moduleUtil->uf_date(request()->input('date'));

        $attendance_data = EssentialsAttendance::where('business_id', $business_id)
                                ->whereDate('clock_in_time', $date)
                                ->whereNotNull('essentials_shift_id')
                                ->with(['shift', 'shift.user_shifts', 'shift.user_shifts.user', 'employee'])
                                ->get();
        $attendance_by_shift = [];
        $date_obj = \Carbon::parse($date);
        foreach ($attendance_data as $data) {
            if (empty($attendance_by_shift[$data->essentials_shift_id])) {
                //Calculate total users in the shift
                $total_users = 0;
                $all_users = [];
                foreach ($data->shift->user_shifts as $user_shift) {
                    if (!empty($user_shift->start_date) && !empty($user_shift->end_date) && $date_obj->between(\Carbon::parse($user_shift->start_date), \Carbon::parse($user_shift->end_date))) {
                        $total_users ++;
                        $all_users[] = $user_shift->user->user_full_name;
                    }
                }
                $attendance_by_shift[$data->essentials_shift_id] = [
                    'present' => 1,
                    'shift' => $data->shift->name,
                    'total' => $total_users,
                    'present_users' => [$data->employee->user_full_name],
                    'all_users' => $all_users
                ];
            } else {
                if (!in_array($data->employee->user_full_name, $attendance_by_shift[$data->essentials_shift_id]['present_users'])) {
                    $attendance_by_shift[$data->essentials_shift_id]['present'] ++;
                    $attendance_by_shift[$data->essentials_shift_id]['present_users'][] = $data->employee->user_full_name;
                }
            }
        }
        return view('essentials::attendance.attendance_by_shift_data')->with(compact('attendance_by_shift'));
    }


    /**
     * Get attendance summary by date
     */
    public function getAttendanceByDate()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $attendance_data = EssentialsAttendance::where('business_id', $business_id)
                                ->whereDate('clock_in_time', '>=', $start_date)
                                ->whereDate('clock_in_time', '<=', $end_date)
                                ->select(
                                    'essentials_attendances.*',
                                    DB::raw("(SELECT COUNT(eus.id) from essentials_user_shifts as eus join essentials_shifts as es ON es.id=essentials_shift_id WHERE es.business_id=$business_id AND  start_date <= clock_in_time AND end_date >= clock_in_time) as total_users_allocated"),
                                    DB::raw("COUNT(DISTINCT essentials_attendances.user_id) as total_present"),
                                    DB::raw('CAST(clock_in_time AS DATE) as clock_in_date')
                                )
                                ->groupBy(DB::raw('CAST(clock_in_time AS DATE)'))
                                ->get();

        $attendance_by_date = [];
        foreach ($attendance_data as $data) {
            $total_users_allocated = !empty($data->total_users_allocated) ? $data->total_users_allocated : 0;
            $total_present = !empty($data->total_present) ? $data->total_present : 0;
            $attendance_by_date[] = [
                    'present' => $total_present,
                    'absent' => $total_users_allocated - $total_present,
                    'date' => $data->clock_in_date
                ];
        }
        return view('essentials::attendance.attendance_by_date_data')->with(compact('attendance_by_date'));
    }

    /**
     * Function to import attendance.
     * @param  Request $request
     * @return Response
     */
    public function importAttendance(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->moduleUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }
            
            //Set maximum php execution time
            ini_set('max_execution_time', 0);

            if ($request->hasFile('attendance')) {
                $file = $request->file('attendance');
                $parsed_array = Excel::toArray([], $file);
                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $formated_data = [];

                $is_valid = true;
                $error_msg = '';
                
                DB::beginTransaction();
                $ip_address = $this->moduleUtil->getUserIpAddr();
                foreach ($imported_data as $key => $value) {
                    $row_no = $key + 1;
                    $temp = [];

                    //Add user
                    if (!empty($value[0])) {
                        $email = trim($value[0]);
                        $user = User::where('business_id', $business_id)->where('email', $email)->first();
                        if (!empty($user)) {
                            $temp['user_id'] = $user->id;
                        } else {
                            $is_valid =  false;
                            $error_msg = "User not found in row no. $row_no";
                            break;
                        }
                    } else {
                        $is_valid =  false;
                        $error_msg = "Email is required in row no. $row_no";
                        break;
                    }

                    //clockin time
                    if (!empty($value[1])) {
                        $temp['clock_in_time'] = trim($value[1]);
                    } else {
                        $is_valid =  false;
                        $error_msg = "Clock in time is required in row no. $row_no";
                        break;
                    }
                    $temp['clock_out_time'] = !empty($value[2]) ? trim($value[2]) : null;

                    //Add shift
                    if (!empty($value[3])) {
                        $shift_name = trim($value[3]);
                        $shift = Shift::where('business_id', $business_id)->where('name', $shift_name)->first();
                        if (!empty($shift)) {
                            $temp['essentials_shift_id'] = $shift->id;
                        } else {
                            $is_valid =  false;
                            $error_msg = "Shift not found in row no. $row_no";
                            break;
                        }
                    }

                    $temp['clock_in_note'] = !empty($value[4]) ? trim($value[4]) : null;
                    $temp['clock_out_note'] = !empty($value[5]) ? trim($value[5]) : null;
                    $temp['ip_address'] = !empty($value[6]) ? trim($value[6]) : $ip_address;
                    $temp['business_id'] = $business_id;
                    $formated_data[] = $temp;
                }

                if (!$is_valid) {
                    throw new \Exception($error_msg);
                }

                if (!empty($formated_data)) {
                    EssentialsAttendance::insert($formated_data);
                }
                
                $output = ['success' => 1,
                        'msg' => __('product.file_imported_successfully')
                    ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return redirect()->back()->with('notification', $output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Adds attendance row for an employee on add latest attendance form
     * @param  int $user_id
     * @return Response
     */
    public function getAttendanceRow($user_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module') || $is_admin)) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::where('business_id', $business_id)
                    ->findOrFail($user_id);

        $attendance = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', $user_id)
                                        ->whereNotNull('clock_in_time')
                                        ->whereNull('clock_out_time')
                                        ->first();

        $shifts = Shift::join('essentials_user_shifts as eus', 'eus.essentials_shift_id', '=', 'essentials_shifts.id')
                    ->where('essentials_shifts.business_id', $business_id)
                    ->where('eus.user_id', $user_id)
                    ->where('eus.start_date', '<=', \Carbon::now()->format('Y-m-d'))
                    ->pluck('essentials_shifts.name', 'essentials_shifts.id');

        return view('essentials::attendance.attendance_row')->with(compact('attendance', 'shifts', 'user'));
    }

    public function shift_attendance(Request $request)
    {
         $business_id = request()->session()->get('user.business_id');
         $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

         if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
             abort(403, 'Unauthorized action.');
         }

        if (request()->ajax()) {
            $shifts = EssentialsUserShift::where('users.business_id',$business_id)
                ->join('users','users.id','essentials_user_shifts.user_id')
                ->join('essentials_shifts','essentials_shifts.id','essentials_user_shifts.essentials_shift_id')

                ->select([
                    'essentials_user_shifts.id',
                    'essentials_shifts.name',
                    'essentials_shifts.type',
                    'essentials_shifts.start_time',
                    'essentials_shifts.end_time',
                    'essentials_shifts.holidays',
                    'essentials_user_shifts.start_date',
                    'essentials_user_shifts.end_date',
                    DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user")


                ])
                ->orderby('start_date','desc');

            $start_date=$request->start_date;
            $end_date=$request->end_date;

         /*  if(!empty($start_date)){
               $shifts->where(function ($query) use ($start_date,$end_date){
                   $query->whereBetween('essentials_user_shifts.start_date',[$start_date,$end_date])
                         ->orwhereBetween('essentials_user_shifts.end_date',[$start_date,$end_date]);
                     });
            }*/


            if(!empty($start_date)){
                $shifts->where(function ($query) use ($start_date,$end_date){
                    $query->where('essentials_user_shifts.start_date','<=',$start_date)
                          ->where('essentials_user_shifts.end_date','>=',$start_date);
                });
            }

           if(!empty($request->employee_id)){
               $shifts->where('essentials_user_shifts.user_id',$request->employee_id);
           }

            return Datatables::of($shifts)
                ->editColumn('start_time', function ($row) {
                    $start_time_formated = $this->moduleUtil->format_time($row->start_time);
                    return $start_time_formated ;
                })
                ->editColumn('end_time', function ($row) {
                    $end_time_formated = $this->moduleUtil->format_time($row->end_time);
                    return $end_time_formated ;
                })
                ->editColumn('type', function ($row) {
                    return __('essentials::lang.' . $row->type);
                })
                ->editColumn('holidays', function ($row) {
                   /* if (!empty($row->holidays)) {
                        $holidays = array_map(function ($item) {
                            return __('lang_v1.' . $item);
                        }, $row->holidays);
                        return implode(', ', $holidays);
                    }*/
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="#" data-href="' . action('\Modules\Essentials\Http\Controllers\AttendanceController@shift_attendance_edit', [$row->id]) . '" data-container="#edit_shift_modal" class="btn-modal btn btn-xs btn-primary edit_shift_modal"><i class="fas fa-edit" aria-hidden="true"></i> ' . __("messages.edit") .'</a> ';
                    $html .= ' <a href="#" data-href="' . action('\Modules\Essentials\Http\Controllers\AttendanceController@shift_attendance_delete', [$row->id]) . '" data-container="#edit_shift_modal" class="btn-modal btn btn-xs btn-danger delete-attendance"><i class="fas fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</a>';




                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'type'])
                ->make(true);
        }

         $settings = request()->session()->get('business.essentials_settings');
         $settings = !empty($settings) ? json_decode($settings, true) : [];

         $is_employee_allowed = !empty($settings['allow_users_for_attendance']) ? true : false;
         $clock_in = EssentialsAttendance::where('business_id', $business_id)
             ->where('user_id', auth()->user()->id)
             ->whereNull('clock_out_time')
             ->first();
         $employees = [];
         if ($is_admin) {
             $employees = User::forDropdown($business_id, false);
         }

         $days = $this->moduleUtil->getDays();

         return view('essentials::shift_attendance.attendance_list')
             ->with(compact('is_admin', 'is_employee_allowed', 'clock_in', 'employees', 'days'));
     }
    public function shift_attendance_list(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $shifts = EssentialsUserShift::where('users.business_id',$business_id)
                ->join('users','users.id','essentials_user_shifts.user_id')
                ->join('essentials_shifts','essentials_shifts.id','essentials_user_shifts.essentials_shift_id')
                ->join('essentials_attendances','essentials_attendances.essentials_user_shifts_id','essentials_user_shifts.id')

                ->select([
                    'essentials_user_shifts.id',
                    'essentials_shifts.name',
                    'essentials_shifts.type',
                    'essentials_attendances.id as row_id',
                    'essentials_attendances.shift_date',
                    'essentials_attendances.clock_in_time',
                    'essentials_attendances.clock_out_time',
                    'essentials_attendances.status as attendance_status',


                    'essentials_shifts.start_time',
                    'essentials_shifts.end_time',
                    'essentials_shifts.holidays',
                    'essentials_user_shifts.start_date',
                    'essentials_user_shifts.end_date',
                    DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user")


                ])
                ->orderby('start_date','desc');

            $start_date=$request->start_date;
            $end_date=$request->end_date;

            /*  if(!empty($start_date)){
                  $shifts->where(function ($query) use ($start_date,$end_date){
                      $query->whereBetween('essentials_user_shifts.start_date',[$start_date,$end_date])
                            ->orwhereBetween('essentials_user_shifts.end_date',[$start_date,$end_date]);
                        });
               }*/


            if(!empty($start_date)){
                $shifts->whereBetween('essentials_attendances.shift_date',[$start_date,$end_date]);
            }

            if(!empty($request->employee_id)){
                $shifts->where('essentials_user_shifts.user_id',$request->employee_id);
            }

            if(!empty($request->status)){
                $shifts->where('essentials_attendances.status',$request->status);
            }

            return Datatables::of($shifts)
                ->editColumn('start_time', function ($row) {
                    $start_time_formated = $this->moduleUtil->format_time($row->start_time);
                    return $start_time_formated ;
                })
                ->editColumn('end_time', function ($row) {
                    $end_time_formated = $this->moduleUtil->format_time($row->end_time);
                    return $end_time_formated ;
                })
                ->editColumn('type', function ($row) {
                    return __('essentials::lang.' . $row->type);
                })
                ->editColumn('attendance_status', function ($row) {
                  $html='---';
                  if($row->attendance_status==0){
                      $html=' <span class="attendance " >لم يتم التوقيع </span>';
                  }

                    if($row->attendance_status==1){
                        $html=' <span class="not_attendance " >تم التوقيع </span>';
                    }

                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                    <button class="btn btn-info dropdown-toggle btn-xs" type="button"  data-toggle="dropdown" aria-expanded="false">
                                        '. __("messages.action").'
                                        <span class="caret"></span>
                                        <span class="sr-only">'
                        . __("messages.action").'
                                        </span>
                                    </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">
                                       <li>
                                          <a href="#" data-href="' . action('\Modules\Essentials\Http\Controllers\AttendanceController@shift_attendance_edit', [$row->id]) . '" data-container="#edit_shift_modal" class="btn-modal edit_shift_modal">
                                          <i class="fas fa-edit" aria-hidden="true"></i> ' . __("essentials::lang.edit_shift") .'</a>
                                        </li>     
                                        
                                        <li>
                                          <a href="#" data-href="' . action('\Modules\Essentials\Http\Controllers\AttendanceController@user_attendance_edit', [$row->row_id]) . '" data-container="#edit_shift_modal" class="btn-modal edit_attendance_modal">
                                          <i class="fas fa-edit" aria-hidden="true"></i> ' . __("essentials::lang.edit_attendance") .'</a>
                                        </li>         
                                    ';

                    $html .= '   </ul>
                              </div>';

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'attendance_status'])
                ->make(true);
        }

        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];

        $is_employee_allowed = !empty($settings['allow_users_for_attendance']) ? true : false;
        $clock_in = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', auth()->user()->id)
            ->whereNull('clock_out_time')
            ->first();
        $employees = [];
        if ($is_admin) {
            $employees = User::forDropdown($business_id, false);
        }

        $days = $this->moduleUtil->getDays();

        return view('essentials::shift_attendance.index')
            ->with(compact('is_admin', 'is_employee_allowed', 'clock_in', 'employees', 'days'));
    }




    public function shift_attendance_edit($id=null)
     {
         $business_id = request()->session()->get('user.business_id');

         if(!empty($id)) {
             $shift = EssentialsUserShift::where('users.business_id', $business_id)
                 ->where('essentials_user_shifts.id', $id)
                 ->join('users', 'users.id', 'essentials_user_shifts.user_id')
                 ->join('essentials_shifts', 'essentials_shifts.id', 'essentials_user_shifts.essentials_shift_id')
                 ->select([
                     'essentials_user_shifts.id',
                     'essentials_user_shifts.user_id',
                     'essentials_shifts.name',
                     'essentials_shifts.type',
                     'essentials_shifts.start_time',
                     'essentials_shifts.end_time',
                     'essentials_shifts.holidays',
                     'essentials_user_shifts.start_date',
                     'essentials_user_shifts.end_date',
                     'essentials_user_shifts.essentials_shift_id',
                     DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user")
                 ])->first();
         }else{
             $shift = new EssentialsUserShift();
         }



         $shifts=Shift::where('business_id',$business_id)->pluck('name','id');
         $currentDateTime = Carbon::now()->format('d-m-y');
         $lastDateOfYear =  Carbon::now()->setMonth(12)->setDay(31)->format('d-m-y');

         $employees = User::forDropdown($business_id, false);
        $html=view('essentials::shift_attendance.edit',compact(['shift','shifts','currentDateTime','lastDateOfYear','employees']))->render();
        return $html;
     }
    public function user_attendance_edit($id=null)
     {
         $business_id = request()->session()->get('user.business_id');

         if(!empty($id)) {
             $shift = EssentialsAttendance::where('essentials_attendances.id', $id)
                 ->join('users', 'users.id', 'essentials_attendances.user_id')
                 ->join('essentials_user_shifts', 'essentials_user_shifts.id', 'essentials_attendances.essentials_user_shifts_id')
                 ->join('essentials_shifts', 'essentials_shifts.id', 'essentials_user_shifts.essentials_shift_id')
                 ->select([
                     'essentials_attendances.id',
                     'essentials_attendances.user_id',
                     'essentials_shifts.name',
                     'essentials_shifts.type',
                     'essentials_shifts.start_time',
                     'essentials_shifts.end_time',
                     'essentials_shifts.holidays',
                     'essentials_attendances.shift_date',
                      'essentials_shifts.id as shift_id',
                      DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user")
                 ])->first();
         }else{
             $shift = new EssentialsUserShift();
         }



         $shifts=Shift::where('business_id',$business_id)->pluck('name','id');
         $currentDateTime = Carbon::now()->format('d-m-y');
         $lastDateOfYear =  Carbon::now()->setMonth(12)->setDay(31)->format('d-m-y');

         $employees = User::forDropdown($business_id, false);
        $html=view('essentials::shift_attendance.user_attendance_edit',compact(['shift','shifts','currentDateTime','lastDateOfYear','employees']))->render();
        return $html;
     }


     public function shift_attendance_update(Request $request)
     {
         $business_id = request()->session()->get('user.business_id');
         $output=['success'=>true,
             'msg'=>'تم حفظ البيان بنجاح'];

         $input=$request->except('_token');
         if(empty($request->user_id)){
             $output=['success'=>false,
                 'msg'=>'عفوا برجاء أختيار الموظف'];
             return $output;
         }
         if(empty($request->essentials_shift_id)){
             $output=['success'=>false,
                 'msg'=>'عفوا برجاء أختيار الوردية'];
             return $output;
         }

         $input['start_date']=Carbon::createFromFormat('d-m-Y', $request->start_date);
         $input['end_date']=Carbon::createFromFormat('d-m-Y', $request->end_date);

         $data=EssentialsUserShift::updateorcreate(['id'=>$request->id],
            $input
         );

         $this->UserAttendaces($data->id);
         return $output;
     }
     public function user_attendance_update(Request $request)
     {
         $business_id = request()->session()->get('user.business_id');
         $output=['success'=>true,
             'msg'=>'تم حفظ البيان بنجاح'];

         $input=$request->except('_token');
         if(empty($request->user_id)){
             $output=['success'=>false,
                 'msg'=>'عفوا برجاء أختيار الموظف'];
             return $output;
         }
         if(empty($request->essentials_shift_id)){
             $output=['success'=>false,
                 'msg'=>'عفوا برجاء أختيار الوردية'];
             return $output;
         }

         $input['start_date']=Carbon::createFromFormat('d-m-Y', $request->start_date);
         $input['end_date']=Carbon::createFromFormat('d-m-Y', $request->end_date);

        /* $data=EssentialsUserShift::updateorcreate(['id'=>$request->id],
            $input
         );*/

         $this->UserAttendaces($request->id);
         return $output;
     }


     public function UserAttendaces($essentials_user_shifts_id)
     {
         $business_id = request()->session()->get('user.business_id');
         $data=EssentialsUserShift::where('id',$essentials_user_shifts_id)->first();
         $input['user_id']=$data->user_id;
         $input['essentials_user_shifts_id']=$data->id;
         $input['business_id']=$business_id;
         $input['shift_date']=$data->start_date;
         $start_date=$data->start_date;
         $end_date=Carbon::parse($data->end_date);

         while( $start_date<=$end_date){
             $input['shift_date']=$start_date;

             $old=EssentialsAttendance::where( 'user_id',$data->user_id)
                 ->where('essentials_user_shifts_id',$data->id)
                 ->where('shift_date', $start_date)->first();

              if(!empty($old)){
                 $old->mark_for_delete=1;
                 $old->save();
             }else{

                 $attendance=EssentialsAttendance::create($input);
             }
              $start_date=Carbon::parse($start_date)->addDays(1)->format('Y-m-d');
         }


     }


     public function shift_attendance_delete($id)
     {
         $business_id = request()->session()->get('user.business_id');
         $shifts = EssentialsUserShift::where('users.business_id',$business_id)
             ->where('essentials_user_shifts.id',$id)
             ->join('users','users.id','essentials_user_shifts.user_id')
             ->join('essentials_shifts','essentials_shifts.id','essentials_user_shifts.essentials_shift_id')
            ->select([
                 'essentials_user_shifts.id',
                 'essentials_shifts.name',
                 'essentials_shifts.type',
                 'essentials_shifts.start_time',
                 'essentials_shifts.end_time',
                 'essentials_shifts.holidays',
                 'essentials_user_shifts.start_date',
                 'essentials_user_shifts.end_date',
                 DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user")


             ])->first();

         $output=['success'=>true,
             'msg'=>' لقد تم حذف سجل حضور : ' .$shifts->user. ' بنجاح '];

         $shifts->delete();

         return $output;
     }


}
