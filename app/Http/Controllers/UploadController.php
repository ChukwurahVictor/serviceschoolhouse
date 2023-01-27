<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class UploadController extends Controller
{
    private function getBaseUrl()
    {
        $explodedUrl = explode("/", url("/"));
        return "https://" . $explodedUrl[2];
    }

    public function uploadModule(Request $req)
    {
        
        $validator = Validator::make($req->all(), [
            'folderzip' => 'required',
            'moduleName' => 'required',
            'moduleDescription' => 'required',
            'courseID' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $moduleName = $req->moduleName;
        $moduleDescription = $req->moduleDescription;
        $courseID = $req->courseID;
        $duration = $req->duration;

        $duration= Carbon::now()->hour(0)->minute(0)->second($duration)->toTimeString();

        //Get the file name and extension
        $folder_name = $req->file("folderzip")->getClientOriginalName();
     
        //Inbuilt Zip package... Extracts zip content, if successful, stores via the $storageDestinationPath else throws an exception
        $zip = new ZipArchive();
        $status = $zip->open($req->file("folderzip")->getRealPath());
        if ($status !== true) {
         throw new \Exception($status);
        }
        else{
            // Folder path matches ssh modules path
            $storageDestinationPath= storage_path("../../ModuleFolders/");
       
            //If folder doesnt exist, it creates a new folder, ignore vs code 'undefined type file' error 
            if (!\File::exists( $storageDestinationPath)) {
                \File::makeDirectory($storageDestinationPath, 0755, true);
            }
            //Zip package extracts content to folder
            $zip->extractTo($storageDestinationPath);
            $zip->close();

            //We get the folder name without extension and add on to the base url. This is saved in the db
            $folder_name = explode(".", $folder_name)[0];
            $folderPath = $this->getBaseUrl() . "/" . "ModuleFolders" . "/" . $folder_name;

            //Add data to db
            $moduleID = DB::table("module")->insertGetId(["moduleName" => $moduleName, "moduleDescription" => $moduleDescription, "courseID" => $courseID, "folder" => $folderPath,"duration" =>$duration]);

            return response()->json(["success" => true, "message" => "Module Added", "moduleID" => $moduleID]);
        }
    }
}
