<?php

namespace App\Http\Controllers;

use App\File;
use App\Notifications\TaskCompleted;
use App\Notifications\VideoCalls;
use Illuminate\Http\Request;
use App\Task;
use App\Quiz;
use App\User;
use App\School;
use App\Attempts;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use \Pusher\Pusher;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){

        $users = User::orderBy('score', 'DESC')->get();

        $usersArray = [] ;
        $singleUsersArray = [];
        $schoolScores = [
            "bukitJalil" =>  User::whereschool_id(1)->sum('score') ,
            "sriPetalling"=> User::whereschool_id(2)->sum('score'),
            "seriKembangan" => User::whereschool_id(3)->sum('score'),
        ];

        foreach ($users as $key => $user) {
            $school = School::find($user->school_id);
            $schoolName = $school->name;

            $userArray = [
                'id' => $user->id,
                'name' => $user->name,
                'level' => $user->level,
                'score' => $user->score,
                'school_name' => $schoolName,
                'parent_name' => $user->parent_name,
                'parent_email' => $user->parent_email,
            ];

            array_push($usersArray, $userArray);
        }


        foreach ($users as $key => $user) {
            $school = School::find($user->school_id);

            if(Auth::user()->school_id == $school->id){
                $schoolName = $school->name;

                $singleUserArray = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'level' => $user->level,
                    'score' => $user->score,
                    'school_name' => $schoolName,
                    'parent_name' => $user->parent_name,
                    'parent_email' => $user->parent_email,
                ];

                array_push($singleUsersArray, $singleUserArray);
            }
        }

        $attemptedQuizes = [];
        $Attempts = Attempts::whereuser_id(Auth::user()->id)->get();

        foreach ($Attempts as $Attempt){
            array_push($attemptedQuizes, $Attempt->quiz_id);
        }

        $quizes =  Quiz::whereNotIn('id', $attemptedQuizes)->where('assigned', '=', 1)->get();


        $quizesCompleted = $Attempts;

        $files = File::all();



        return view('dashboard', compact('quizes', 'quizesCompleted', 'users', 'singleUsersArray', 'schoolScores', 'usersArray', 'files'));
    }

//    Tasks

    public function  taskShow($id){

            $Task = Task::wherequiz_id($id)->get();
            $TaskEncoded = json_encode($Task);

            $QuizID = $Task[0]->quiz_id;
            $Quiz = Quiz::whereid($QuizID)->first();
            $QuizName = $Quiz->name;

            $User = User::find(Auth::user()->id);
            $UserSchool = $User->school_id;

            Attempts::create([
                'user_id' => Auth::user()->id,
                'school_id'=> $UserSchool,
                'quiz_id' => $id,
                'attempted' => 1,
                'score' => 0
            ]);

        return view('task',compact('TaskEncoded', 'QuizName', 'QuizID'));
    }


    public function taskScore(Request $request){
        $user = User::find(Auth::user()->id);
        $user->score += $request->score;
        $user->save();


        Notification::route('mail', $user->parent_email)
            ->notify(new TaskCompleted($user->name, $request->score, $user->parent_name));
        return route('user.dashboard');
    }


}
