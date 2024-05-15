<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\TodoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManageTodoController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = $request->input('paginate') ?? 12;
        $search = $request->input('search') ?? null;
        $page = $request->input('page') ?? 1;
        $result = TodoList::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })->latest()->paginate($paginate, ['*'], 'page', $page);
        return customResponse(true, 200, 'User fetched successfully', $result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ])->validate();

        $user = auth('sanctum')->user();
        //check if user already exists
        $result = TodoList::where('title', $request->title)
            ->where('user_id', $user->id)
            ->first();
        if ($result) {
            return customResponse(false, 400, 'Todo already exists');
        }

        $result = new TodoList();
        $result->title = $request->title;
        $result->description = $request->description;
        $result->user_id = $user->id;
        $result->save();

        return customResponse(true, 200, 'Todo created successfully', $result);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ])->validate();

        $user = auth('sanctum')->user();

        $todo = TodoList::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (!$todo) {
            return customResponse(false, 400, 'Todo not found');
        }

        $todo->title = $request->title;
        $todo->description = $request->description;
        $todo->save();

        return customResponse(true, 200, 'Todo updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = auth('sanctum')->user();
        $todo = TodoList::find($id)
            ->where('user_id', $user->id)
            ->first();
        if (!$todo) {
            return customResponse(false, 400, 'Todo not found');
        }
        $todo->delete();
        return customResponse(true, 200, 'Todo deleted successfully');
    }
}
