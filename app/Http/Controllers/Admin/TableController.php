<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Session;
use Auth;
use Input;
use App\Models\Admin\Member;
use App\Models\Admin\Manager;


class TableController extends Controller
{
    public function index(Request $request){

        // $name = Manager::get()->first();
        $name = Auth::guard('member')->user();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => Session::get('tableau_domain')."/trusted?username=".$name->tableau_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"username\":\"admin\"}",
        CURLOPT_HTTPHEADER => array(
            "User-Agent: TabCommunicate",
            "Content-Type: application/json",
            "Accept: application/json",
          ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
            Session::put("ticket",$response);
        }

        $contentUrl = $request->all()['contentUrl'];
        $array = explode("/", $contentUrl);
        array_splice($array,1,1);
        $contentUrl = implode("/", $array);
        $ticket = Session::get('ticket');
        return view('admin.table.index',compact('contentUrl','ticket'));
    }

    public function status(){
        $data = Input::all();
        // dd($data);
        if($data['type'] == '1'){
            //创建table用户
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => Session::get('tableau_domain')."/api/3.2/sites/".Session::get('credentials')."/users",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<tsRequest><user name=\"" . $data['username'] ."\" siteRole=\"Interactor\" authSetting=\"ServerDefault\" /></tsRequest>",
            CURLOPT_HTTPHEADER => array(
                "X-Tableau-Auth: ". Session::get('token'),
                "Accept: application/json",
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              return '0';
            } else {
                $res = json_decode($response);
                $result = Member::where('id',$data['id'])->get()->first();
                $result['tableau_id'] = $res->user->id;
                $result->status = 1;
                $result->save();

                return '1';
            }
        }else{
            // // dd($data);
            // //删除用户
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => Session::get('tableau_domain')."/api/3.2/sites/".Session::get('credentials')."/users/".$data['tableau_id'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => array(
                "X-Tableau-Auth: ".Session::get('token'),
                "Accept: application/json",
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $result = Member::where('tableau_id',$data['tableau_id'])->get()->first();
            $result['tableau_id'] = null;
            $result->status = 2;
            $result->save();
            return '1';
        }
    }

    //报表权限的分配
    public function auth($id){
        $user = Member::where('id',$id)->get()->first();
        if(Input::method() == 'POST'){
            $tableauIds = Input::get('tableauIds');
            $stringIds = implode(',',$tableauIds);
            $user -> tableauIds = $stringIds;
            $result = $user -> save();
            return $result ? '1':'0';
        }else{
            /*拿到所有报表的数据*/
            $curlt = curl_init();

            /*获取用户的信息*/
            curl_setopt_array($curlt, array(
            CURLOPT_URL =>  Session::get('tableau_domain')."/api/3.2/sites/".Session::get('credentials')."/workbooks/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            // CURLOPT_COOKIE =>"token=".Session::get('token'),
            CURLOPT_HTTPHEADER => array(
                "X-Tableau-Auth: ".Session::get('token'),
                "Accept: application/json",
              ),
            ));
            $response = curl_exec($curlt);
            $err = curl_error($curlt);
            curl_close($curlt);
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              // $response = simplexml_load_string($response);
                $data = json_decode($response)->workbooks->workbook;
            }
            $hasTableauIds = explode(',',$user->tableauIds);
            return view('admin.table.authIndex',compact('data','hasTableauIds'));//展示报表列表
        }
    }

    public function user($id){
        $mamber = Member::where('id',$id)->get()->first();
        if(Input::method() == 'POST'){
            $tableau_id = Input::get('tableau_id');
            dd($tableau_id);
            $mamber->tableau_id = $tableau_id;
            $result = $mamber->save();
            return $result ? '1':'0';
        }else{
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "http://tableau.kalaw.top/api/3.2/sites/".Session::get('credentials')."/users",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "X-Tableau-Auth:".Session::get('token'),
                "Accept: application/json",
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $tsResponse = json_decode($response)->users->user;
              dd($tsResponse);
            }
            return view('admin.table.user',compact('tsResponse','mamber'));
        }
    }
}
