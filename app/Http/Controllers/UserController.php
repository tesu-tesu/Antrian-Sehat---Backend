<?php

namespace App\Http\Controllers;

use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
   public function __construct() {
       $this->middleware('roleUser:Admin,Super Admin')->only(['show']);
       $this->middleware('roleUser:Super Admin')->only(['getAdminUser']);
   }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getAdminUser(){
        $admins = User::where('role', "Admin")->with('health_agency')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data user admin selected',
            'data' => $admins
        ], 200);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:3,150',
            'email' => 'required|string|email|unique:users|max:100',
            'password' => 'required|string|min:6',
            'phone' => 'required|numeric|digits_between:8,13',
            'role' => 'required|string',
            'residence_number' => 'nullable|numeric|unique:users|digits:16',
            'health_agency' => 'nullable|numeric'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'residence_number' => $request->residence_number,
            'health_agency_id' => $request->health_agency,
            'password' => bcrypt($request->password)
        ]);

        $user = User::where('id', $user->id)->with('health_agency')->first();
        if($user)
            return response()->json([
                'success' => true,
                'message' => 'User has successfully created',
                'data' => $user
            ], 200);
        else
            return response()->json([
                'success' => true,
                'message' => 'User has failed created',
            ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user = User::where('id', $user->id)->with('health_agency')->first();
        return response()->json($user, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:3,150',
            'email' => 'required|string|email|unique:users,email,' .$user->id. '|max:100',
            'password' => 'required|string|min:6',
            'phone' => 'required|numeric|digits_between:8,13',
            'role' => 'required|string',
            'residence_number' => 'nullable|numeric|unique:users,residence_number,' .$user->id. '|digits:16',
            'health_agency' => 'nullable|numeric'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $updated = User::where('id', $user->id)
            ->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'residence_number' => $request->residence_number,
                'health_agency_id' => $request->health_agency,
                'password' => bcrypt($request->password)
            ]);

        $newUser = User::where('id', $user->id)->with('health_agency')->first();

        if ($updated)
            return response()->json([
                'success' => true,
                'message' => 'User data updated successfully!',
                'data' => $newUser
            ], 200);
        else
            return response()->json([
                'success' => false,
                'message' => 'User data can not be updated'
            ], 200);
    }

    public function changePassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'current' => ['required', new MatchOldPassword()],
            'new' => ['required', 'string', 'max:255'],
            'confirm' => ['same:new'],
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $updated = User::where('id', $user->id)
            ->update([
                'password' => bcrypt($request->new)
            ]);

        if ($updated)
            return response()->json([
                'success' => true,
                'message' => 'Password data updated successfully!'
            ], 200);
        else
            return response()->json([
                'success' => false,
                'message' => 'Password data can not be updated'
            ], 200);
    }

    public function changeImage(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'image|mimes:jpeg,png,jpg|max:2000',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $uploadFile = $request->file('image');
        if($uploadFile!=null){
            \File::delete(storage_path('app/').$user->profile_img);
            $path = $uploadFile->store('public/img/users');
        } else {
            $path = $user->image;
        }
        $updated = User::where('id', $user->id)
            ->update([
                'profile_img' => $path
            ]);

        if ($updated)
            return response()->json([
                'success' => true,
                'message' => 'Profile image has updated successfully!'
            ], 200);
        else
            return response()->json([
                'success' => false,
                'message' => 'Profile image can not be updated'
            ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        if ($user->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'User has successfully deleted'
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User can not be deleted'
            ], 200);
        }
    }

    public function getResidenceNumber() {
        $user = Auth::user()->residence_number;

        if($user != null) {
            return response()->json([
                'success' => true,
                'message' => 'Success get the residence number',
                'data' => $user,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User doesn\'t have residence number',
                'data' => 0,
            ], 200);
        }
    }
}
