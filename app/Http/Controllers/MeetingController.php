<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();

        foreach($meetings as $meeting) {
            $meeting->view_meeting=[
                'href' => 'api/v1/meeting/'.$meeting->id,
                'method' => 'GET',
            ];
        }

        $response = [
            'msg' => 'List of meetings',
            'meetings' => $meetings
        ];

        return response()->json($response, 200);
    }
 

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validasi
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required',
        ]);

        //data dari inputan
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        // objek instance meeting
        $meeting = new Meeting([
            'time' => $time,
            'title' => $title,
            'description' => $description
        ]);
        // users()->attach untuk membuat pivot di tabel meeting_user
        if ($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET'
            ];

            $message = [
                'msg' => 'Meeting created',
                'meeting' => $meeting
            ];
            return response()->json($message, 201);
        }

        // response eror
        $response = [
            'msg' => 'Eror during creation'
        ];
        return response()->json($response, 404);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET',
        ];

        $response = [
            'msg' => 'Meeting information',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
    }

   

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:Y-m-d H:i:s',
            'user_id' => 'required'
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = Meeting::with('users')->findOrFail($id);

        // jika user tidak samadengan user id yg daftar pertama
        if(!$meeting->users()->where('user_id', $user_id)->first()) {
            return response()->json(
                ['msg' => 'User not registered for meeting, update not successful'], 401
            );
        }

        $meeting->title = $title;
        $meeting->description = $description;
        $meeting->time = $time;

        // jika meeting gagal update
        if(!$meeting->update()){
            return response()->json([
                'msg' => 'eror during update'
            ], 404);
        }

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting'. $meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting update',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $users = $meeting->users; // mengambil data user yg terkait dg meeting
        $meeting->users()->detach(); // sebelum mengapus meeting lakukan detach()->mengapus data2 user_id yg terkait pada meeting didalam tabel pivot 

        // jika meeting gagal hapus
        // user_id yg di detach dikembalikan
        if(!$meeting->delete()) {
            foreach($users as $user){
                $meeting->users()->attach($user);
            }
            return response()->json([
                'msg' => 'Delete failed'
            ], 404);
        }

        // jika berhasil hapus
        $response = [
            'msg' => 'Meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
            ];
        return response()->json($response, 200);
    }
}
