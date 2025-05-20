<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\UploadFolder;
use Response;
use Auth;
use Image;
use ZipArchive;



class FileUploadController extends Controller
{


    public function index(Request $request)
    {
        $search = $request->search;
        $sort_by = $request->sort;
        $current_folder_id = $request->folder_id ?? 0;

        $folders = UploadFolder::where('parent_id', $current_folder_id)->get();

        $all_uploads = Upload::where('user_id', Auth::id())
            ->where('folder_id', $current_folder_id);

        if ($search) {
            $all_uploads->where('file_original_name', 'like', '%' . $search . '%');
        }

        switch ($sort_by) {
            case 'oldest':
                $all_uploads->orderBy('created_at', 'asc');
                break;
            case 'smallest':
                $all_uploads->orderBy('file_size', 'asc');
                break;
            case 'largest':
                $all_uploads->orderBy('file_size', 'desc');
                break;
            default:
                $all_uploads->orderBy('created_at', 'desc');
        }

        $all_uploads = $all_uploads->paginate(24)->appends($request->query());

        return view('admin.uploaded_files.index', compact('folders', 'all_uploads', 'search', 'sort_by', 'current_folder_id'));
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $folder = new UploadFolder();
        $folder->name = $request->name;
        $folder->user_id = Auth::id();
        $folder->parent_id = $request->parent_id;
        $folder->save();

        flash(translate('Folder created successfully'))->success();
        return back();
    }

    public function show_uploader(Request $request){
        $folder_id = $request->folder_id;
        return view('admin.uploaded_files.uploader', compact('folder_id'));
    }

    public function create(Request $request, $folder_id = 0){
        // dd($request, $folder_id);
        return view('admin.uploaded_files.create', compact('folder_id'));
    }
    public function show(Request $request){
        // dd($request);
        $folder_id = $request->folder_id;
        return view('admin.uploaded_files.create', compact('folder_id'));
    }


    
    public function uploadFolder(Request $request)
    {
        $files = $request->file('smf_file');
        $paths = $request->input('paths', []);

        if (!$files || !is_array($files)) {
            return response()->json(['success' => false, 'message' => 'No files uploaded'], 400);
        }

        foreach ($files as $index => $file) {
            $relativePath = $paths[$index] ?? $file->getClientOriginalName();
            $originalName = $file->getClientOriginalName();

            $parentFolderId = $request->folder_id;

            // Check if this is a folder upload based on presence of path separators
            if (str_contains($relativePath, '/') && dirname($relativePath) !== '.') {
                $relativeFolderPath = dirname($relativePath);
                $segments = explode('/', $relativeFolderPath);

                foreach ($segments as $segment) {
                    if (empty($segment)) continue;

                    $existing = UploadFolder::where('name', $segment)
                        ->where('parent_id', $parentFolderId)
                        ->first();

                    if ($existing) {
                        $parentFolderId = $existing->id;
                    } else {
                        $folder = new UploadFolder();
                        $folder->name = $segment;
                        $folder->parent_id = $parentFolderId;
                        $folder->user_id = auth()->id();
                        $folder->save();
                        $parentFolderId = $folder->id;
                    }
                }
            }

            $path = $file->store('uploads/all');

            $upload = new Upload();
            $upload->file_original_name = $originalName;
            $upload->file_name = $path;
            $upload->folder_id = $parentFolderId;
            $upload->user_id = auth()->id();
            $upload->extension = $file->getClientOriginalExtension();
            $upload->type = $this->getFileType($upload->extension);
            $upload->file_size = $file->getSize();
            $upload->save();
        }

        return response()->json(['success' => true]);
    }





    // Helper for determining type
    private function getFileType($extension)
    {
        $types = [
            'image' => ['jpg','jpeg','png','svg','webp','gif'],
            'video' => ['mp4','mpg','mpeg','webm','ogg','avi','mov','flv','swf','mkv'],
            'audio' => ['wma','aac','wav','mp3'],
            'archive' => ['zip','rar','7z'],
            'document' => ['doc','txt','docx','pdf','csv','xml','ods','xlr','xls','xlsx']
        ];

        foreach ($types as $type => $exts) {
            if (in_array($extension, $exts)) return $type;
        }

        return 'others';
    }




    public function get_uploaded_files(Request $request)
    {
        $uploads = Upload::where('user_id', Auth::user()->id);
        if ($request->search != null) {
            $uploads->where('file_original_name', 'like', '%'.$request->search.'%');
        }
        if ($request->sort != null) {
            switch ($request->sort) {
                case 'newest':
                    $uploads->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $uploads->orderBy('created_at', 'asc');
                    break;
                case 'smallest':
                    $uploads->orderBy('file_size', 'asc');
                    break;
                case 'largest':
                    $uploads->orderBy('file_size', 'desc');
                    break;
                default:
                    $uploads->orderBy('created_at', 'desc');
                    break;
            }
        }
        return $uploads->paginate(60)->appends(request()->query());
    }

    public function destroy(Request $request,$id)
    {
        $upload = Upload::findOrFail($id);

        if(auth()->user()->user_type == 'seller' && $upload->user_id != auth()->user()->id){
            flash(translate("You don't have permission for deleting this!"))->error();
            return back();
        }
        try{
            if(env('FILESYSTEM_DRIVER') == 's3'){
                Storage::disk('s3')->delete($upload->file_name);
            }
            else{
                unlink(public_path().'/'.$upload->file_name);
            }
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        }
        catch(\Exception $e){
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        }
        return back();
    }

    public function get_preview_files(Request $request){
        $ids = explode(',', $request->ids);
        $files = Upload::whereIn('id', $ids)->get();
        return $files;
    }

    //Download project attachment
    public function attachment_download($id)
    {
        $project_attachment = Upload::find($id);
        try{
           $file_path = public_path($project_attachment->file_name);
            return Response::download($file_path);
        }catch(\Exception $e){
            flash(translate('File does not exist!'))->error();
            return back();
        }

    }
    //Download project attachment
    public function file_info(Request $request)
    {
        $file = Upload::findOrFail($request['id']);

        return view('admin.uploaded_files.info',  compact('file'));
    }


    // Rename folder
    public function renameFolder(Request $request)
    {
        $folder = UploadFolder::find($request->id);

        if ($folder) {
            $folder->name = $request->name;
            $folder->save();

            return response()->json(['success' => true, 'message' => 'Folder renamed successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
    }

    // Delete folder and its files
    public function deleteFolder($id)
    {
        $folder = UploadFolder::findOrFail($id);

        // Delete all files in this folder using the same logic as 'destroy'
        $uploads = Upload::where('folder_id', $id)->get();
        foreach ($uploads as $upload) {
            // dd(public_path().'/'.$upload->file_name);
            try {
                if (env('FILESYSTEM_DRIVER') == 's3') {
                    Storage::disk('s3')->delete($upload->file_name);
                } else {
                    if (file_exists(public_path().'/'.$upload->file_name)) {
                        unlink(public_path().'/'.$upload->file_name);
                    }
                }
                $upload->delete();
            } catch (\Exception $e) {
                $upload->delete();
            }
        }

        // Delete subfolders recursively (optional - depends on your logic)
        $this->deleteSubfolders($id);

        // Delete the folder itself
        $folder->delete();

        flash(translate('Folder and its contents deleted.'))->success();
        return back();
    }

    // Helper method for recursive subfolder deletion
    private function deleteSubfolders($parent_id)
    {
        $subfolders = UploadFolder::where('parent_id', $parent_id)->get();

        foreach ($subfolders as $subfolder) {
            $this->deleteFolder($subfolder->id);
        }
    }

    public function downloadFolderAsZip($folderId)
    {
        
        $folder = UploadFolder::with(['files', 'children'])->findOrFail($folderId);

        $zipFileName = Str::slug($folder->name) . '-' . time() . '.zip';
        $zipPath = public_path('uploads/all/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $this->addFolderToZip($zip, $folder, '');
            $zip->close();
        } else {
            return response()->json(['error' => 'Failed to create ZIP file'], 500);
        }

        if (!file_exists($zipPath)) {
            return response()->json(['error' => 'ZIP file not found'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    protected function addFolderToZip(ZipArchive $zip, UploadFolder $folder, $parentPath = '')
    {   
        $currentPath = $parentPath ? $parentPath . '/' . $folder->name : $folder->name;

        // Add empty folder entry in zip
        $zip->addEmptyDir($currentPath);

        // Add files in this folder
        // dd($folder->files);
        foreach ($folder->files as $file) {
            $filePath = public_path($file->file_name);
            // dd($filePath);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $currentPath . '/' . $file->file_original_name);
            }
        }

        // Recursively add subfolders
        foreach ($folder->children as $subfolder) {
            $this->addFolderToZip($zip, $subfolder, $currentPath);
        }
    }


}