<?php

namespace App\Http\Controllers;

use App\Report;
use App\User;
use App\Formation;
use App\FormationDetail;
use App\ReportComment;
use App\Student;
use App\Http\Resources\Report as ReportR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class ReportController extends Controller
{
    public function reportsForAdmin()
    {
      if(Auth::user()->user_type_id):
          $reports = Report::select('*')->get();
          return response::json($reports);
      endif;
    }
    public function getReports($dateFilter, $userFilter)
    {
      if(Auth::user()->user_type_id == 1):
        $reports = Report::select('reports.id as report_id','student_id','reports.title', 'users.firstname as studentFirstname', 'users.lastname as studentLastname', 'date', 'rate', 'text', 'is_daily')
        ->leftjoin('users', 'users.id', 'reports.student_id');
        
        if($dateFilter !== 'allDate') :
          $reports->where('date', $dateFilter);
        endif;

        if($userFilter !== 'allUser') :
          $reports->where('student_id', $userFilter);
        endif;
        $results = $reports->get();
        return Response::json($results);
      else: 
        return Response::json(["Erreur : "=>"Vous n'avez pas les droits"]);
      endif;
    }

    /**
     * Get a student's report 
     * Return Response
     */

     public function getReportsByFormationIdAndStudentId($reportId, $formationId)
     {
       if((Auth::user()->user_type_id == 2) || (Auth::user()->user_type_id == 3)):

      // dd($formationId);
      $reportByFormationId = Report::
        select('reports.id as report_id',
               'reports.student_id as student_id',
               'reports.date as report_date',
               'reports.is_daily as report_is_daily',
               'reports.text',
               'reports.rate',
               'reports.created_at',
               'reports.title',
               'reports.updated_at',
               'students.formation_id'
              )
        ->join('students', 'students.user_id', 'reports.student_id')
        // ->join('report_comments', 'reports.id', 'report_comments.report_id')      
        ->where([
            ['students.formation_id', $formationId],
            ['reports.id', $reportId]
            
            ])      
          ->get()->toArray();
        
        foreach($reportByFormationId as $key=>$report):
          // dd($report);
            $user = User::where('id', $report['student_id'])->select('lastname', 'firstname')->get();
            $reportComment = ReportComment::join('users', 'users.id', 'report_comments.user_id')->where('report_comments.report_id', $report['report_id'])->select('report_comments.id','report_comments.text','report_comments.user_id','users.lastname', 'users.firstname', 'report_comments.created_at')->get();
            $reportByFormationId[$key]['student'] = $user;
            $reportByFormationId[$key]['comments'] = $reportComment;
        endforeach;

      return Response::json($reportByFormationId);
    else:
      return Response::json(["Erreur : "=>"Vous n'avez pas les droits"]);
    endif;

     }
    /**
     * Get all the reports of a formation
     * Return Response
     */

    /**
    * Get one report by reportId
    */

    public function getReportsCreator()
    {
      $report = Report::select('student_id')->get();
      $student = User::select('users.id as user_id','students.id as student_id', 'students.user_id','users.lastname','users.firstname')
        ->join('students', 'students.user_id', 'users.id')
        ->whereIn('users.id', $report)
        ->get();
        return response::json($student);
    }

    public function getOneReport($reportId, $formationId)
    {
      if(Auth::user()->user_type_id == 2):
        $reportByFormationId = Report::
        select('reports.id as report_id',
              'reports.student_id as student_id',
              'reports.date as report_date',
              'reports.text as text',
              'reports.is_daily as report_is_daily',
              'reports.title as report_title',
              'reports.created_at',
              'reports.updated_at',
              'students.formation_id'
              )
        ->join('students', 'students.user_id', 'reports.student_id')
        ->where('reports.id', $reportId)
        ->where('students.formation_id', $formationId)      
        ->get()->toArray();
          
        foreach($reportByFormationId as $key=>$report):
          
          // dd($report);
          $user = User::where('id', $report['student_id'])->select('lastname', 'firstname')->get();
          $reportComment = ReportComment::join('users', 'users.id', 'report_comments.user_id')->where('report_comments.report_id', $report['report_id'])->select('report_comments.id','report_comments.text','report_comments.user_id','users.lastname', 'users.firstname', 'report_comments.created_at')->get();
          $reportByFormationId[$key]['student'] = $user;
          $reportByFormationId[$key]['comments'] = $reportComment;
    
            $reportByFormationId[$key]['author'] = $user;
        endforeach;
        return response::json($reportByFormationId);
      else:
        return Response::json(["Erreur : "=>"Vous n'avez pas les droits"]);
      endif;
    }
    public function getreportsByFormationId($formationId)
    {
      if(Auth::user()->user_type_id == 2):

        // dd($formationId);
        $reportByFormationId = Report::
          select('reports.id as report_id',
                 'reports.student_id as student_id',
                 'reports.date as report_date',
                 'reports.rate as report_rate',
                 'reports.title as report_title',
                 'reports.is_daily as report_is_daily',
                 'reports.created_at',
                 'reports.updated_at',
                 'students.formation_id'
                )
          ->join('students', 'students.user_id', 'reports.student_id')
          ->where('students.formation_id', $formationId)      
          ->get()->toArray();
          
          foreach($reportByFormationId as $key=>$report):
            
              $user = User::where('id', $report['student_id'])->select('lastname', 'firstname')->get();
      
              $reportByFormationId[$key]['student'] = $user;
          endforeach;

        return Response::json($reportByFormationId);
      else:
        return Response::json(["Erreur : "=>"Vous n'avez pas les droits"]);
      endif;
    }

    public function show($reportId)
    {
      if(Auth::user()->user_type_id == 1):
          $report = Report::select('reports.id as report_id','student_id', 'users.firstname as studentFirstname', 'users.lastname as studentLastname', 'date', 'rate', 'text', 'is_daily', 'title')
          ->where('reports.id', $reportId)
          ->leftjoin('users', 'users.id', 'reports.student_id')
          ->get()->first();
          return Response::json($report);
        else:
          return Response::json(["Erreur : "=>"Vous n'avez pas les droits"]);
      endif;
    }

    public function store(Request $request)
    {
        $authUserId = Auth::user()->id;
        $authUserTypeId = Auth::user()->user_type_id;

        //si la méthode est un put, on effectue la modification
        if($authUserTypeId):
          if($request->isMethod('put')):
            $report = 
              Report::where([
                [ 'id', '=', $request->report_id],
              ])->get()->first();
      
            if(!empty($report)):
              $report->id = $request->input('report_id');
              $report->student_id = $authUserId;
              $report->rate = $request->input('rate');
              $report->date = $request->input('date');
              $report->title = $request->input('title');
              $report->text = $request->input('text');
              $report->is_daily = $request->input('is_daily');
      
              // dd($formation);
      
              if($report->save()):
                return new ReportR($report);
              endif;

            endif;
          
          //fin de la modification, ici on crée un nouveau commentaire
          else:
            $input = $request->all();
            $input['student_id'] = $authUserId;
            $report = Report::create($input);
            return new ReportR($report);
          endif;
        else:
          return Response::json(['error'=>"Accès non autorisé"]);
        endif;
    }

    public function destroy($reportId)
    {
      $report = Report::findOrFail($reportId);
      $authUserTypeId = Auth::user()->user_type_id;

      if($authUserTypeId == 1):
        if($report->delete()):
          return new ReportR($report);
        endif;
      else:
        return Response::json(['error'=>"Accès non autorisé"]);
      endif;
    }


    /**
     * Récupération des rapports de la formation auquelle l'étudiant connecté appartient
     */
    
    public function getStudentsReport()
    {
      $authUserTypeId = Auth::user()->user_type_id;
      $authUserId = Auth::user()->id;
      // dd(Auth::user());
      $formationData = Student::select('students.id as student_id', 'formations.name as formation_name', 'formations.id as formation_id','formations.start_at as formation_start_at', 'formations.end_at as formation_end_at' )
      ->join('formations', 'formations.id', 'students.formation_id')
      ->where('students.user_id', $authUserId)
      ->orderBy('students.id', 'desc')
      ->get()->first();

      $reports = Report::select('reports.id as report_id','student_id', 'students.formation_id as formation_id','users.firstname as studentFirstname', 'users.lastname as studentLastname','reports.updated_at as last_edit_date', 'reports.created_at as created_date', 'reports.title as report_title',  'rate', 'text', 'is_daily')
      ->leftjoin('users', 'users.id', 'reports.student_id')
      ->leftjoin('students', 'students.user_id', 'reports.student_id')
      ->where('students.formation_id', $formationData->formation_id)
      ->where('reports.student_id', Auth::user()->id)
      ->paginate(25);

      // dd($authUserId);
      return Response::json($reports);

    }
    public function getStudentsReportByFormationId()
    {
      $authUserTypeId = Auth::user()->user_type_id;
      $authUserId = Auth::user()->id;
      // dd(Auth::user());
      $formationData = Student::select('students.id as student_id', 'formations.name as formation_name', 'formations.id as formation_id','formations.start_at as formation_start_at', 'formations.end_at as formation_end_at' )
        ->join('formations', 'formations.id', 'students.formation_id')
        ->where('students.user_id', $authUserId)
        ->orderBy('students.id', 'desc')
        ->get()->first();

      $reports = Report::select('reports.id as report_id','student_id', 'formations.name','students.formation_id as formation_id','users.firstname as studentFirstname', 'users.lastname as studentLastname','reports.updated_at as last_edit_date', 'reports.created_at as created_date', 'reports.date as report_date','reports.title as report_title', 'reports.rate as report_rate','text', 'is_daily')
        ->join('users', 'users.id', 'reports.student_id')
        ->join('students', 'students.user_id', 'reports.student_id')
        ->join('formations', 'formations.id', 'students.formation_id')
        ->where('students.formation_id', $formationData->formation_id)
        // ->where('reports.', Auth::user()->id)
        ->paginate(25);

      // dd($authUserId);
      return Response::json($reports);

    }

}
