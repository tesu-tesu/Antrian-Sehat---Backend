<?php

namespace App\Http\Controllers;

use App\HealthAgency;
use App\Polyclinic;
use App\PolyMaster;
use App\WaitingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HealthAgencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse|Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'address' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'call_center' => 'required',
            'email' => 'required|email|unique:health_agencies',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        //Checking File
        $uploadFile = $request->file('image');
        if($uploadFile){
            $path = $uploadFile->store('public/img/health_agencies');
        }else{
            $path = null;
        }

        $health_agency = HealthAgency::create([
            'name' => $request->name,
            'address' => $request->address,
            'image' => $path,
            'call_center' => $request->call_center,
            'email' => $request->email,
        ]);

        if($health_agency)
            return response()->json([
                'success' => true,
                'message' => 'Add data successfully!',
                'user' => $health_agency,
            ], 200);
        else
            return response()->json([
                'success' => false,
                'message' => 'Add data failed!',
                'user' => $health_agency,
            ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\HealthAgency  $healthAgency
     * @return JsonResponse|Response
     */
    public function show(HealthAgency $healthAgency)
    {
        return response()->json($healthAgency, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HealthAgency  $healthAgency
     * @return \Illuminate\Http\Response
     */
    public function edit(HealthAgency $healthAgency)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HealthAgency  $healthAgency
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, HealthAgency $healthAgency)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'address' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'call_center' => 'required',
            'email' => 'required|email|unique:health_agencies,email,'.$healthAgency->id,
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        //Checking File
        $uploadFile = $request->file('image');
        if($uploadFile!=null){
            \File::delete(storage_path('app/').$healthAgency->image);
            $path = $uploadFile->store('public/img/health_agencies');
        } else {
            $path = $healthAgency->image;
        }

        $isUpdate = HealthAgency::where('id', $healthAgency->id)->first()
            ->update([
                'name' => $request->name,
                'address' => $request->address,
                'image' => $path,
                'call_center' => $request->call_center,
                'email' => $request->email,
            ]);

        $health_agency = HealthAgency::where('id', $healthAgency->id)->first();

        if($isUpdate)
            return response()->json([
                'success' => true,
                'message' => 'Update data successfully!',
                'user' => $health_agency,
            ], 200);
        else
            return response()->json([
                'success' => false,
                'message' => 'Update data failed!',
                'user' => $health_agency,
            ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HealthAgency  $healthAgency
     * @return JsonResponse|Response
     */
    public function destroy(HealthAgency $healthAgency)
    {
        \File::delete(storage_path('app/').$healthAgency->image);

        if ($healthAgency->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'Delete data successfully!',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Delete data failed!',
            ], 500);
        }
    }

    public function userShowPolymaster(HealthAgency $healthAgency){
        $puskesmas = Polyclinic::where('health_agency_id', $healthAgency->id)
            ->with('poly_master')->get();

        $results = [];
        foreach ($puskesmas as $row) {
            $results[] = $row;
        }

        return response()->json($results, 200);
    }

    public function adminShowPolyclinic(HealthAgency $healthAgency){
        $schedule = Polyclinic::with(['poly_master' => function($q){
                $q->select('id', 'name')->get();
            },'schedules'])
            ->where('health_agency_id', $healthAgency->id)->get();

        return response()->json($schedule, 200);
    }

    public function showWaitingList(){
        $waiting_list = DB::table('waiting_lists as wa')
            ->join('users as us', 'wa.user_id', '=', 'us.id')
            ->join('schedules as sc', 'wa.schedule_id', '=', 'sc.id')
            ->join('polyclinics as po', 'sc.polyclinic_id', '=', 'po.id')
            ->join('poly_masters as pm', 'po.poly_master_id', '=', 'pm.id')
            ->where('po.health_agency_id', '=', Auth::user()->health_agency_id)
            ->select('wa.*', 'pm.name as poly_name', 'us.name as user_name', 'us.email')
            ->get();

        return response()->json($waiting_list);
    }

    public function searchHealthAgency(Request $request){
        $search = $request->q;
        if ($search != null){
            $query1 = HealthAgency::where('name', 'like', '%' .$search. '%')->get();
            $query2 = PolyMaster::where('name', 'like', '%' .$search. '%')
                ->with('polyclinics', 'polyclinics.health_agency')->get();

            foreach ($query1 as $item) {
                $results[] = $item;
            }
            foreach ($query2 as $polymaster) {
                foreach ($polymaster->polyclinics as $polyclinic) {
                    if (!in_array($polyclinic->health_agency, $results)){
                        $results[] = $polyclinic->health_agency;
                    }
                }
            }
        }else{
            $results = null;
        }

        return response()->json($results);
    }
}
