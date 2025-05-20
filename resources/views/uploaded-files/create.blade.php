@extends('admin.layouts.master')

@section('title')
    Upload File
@endsection

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('Upload New File')}}</h1>
		</div>
		<div class="col-md-6 text-md-right">
            <a href="{{ url()->previous() }}" class="btn btn-link text-reset">
                <i class="fas fa-angle-left"></i>
                <span>{{ translate('Back to folder') }}</span>
            </a>
        </div>
	</div>
</div>
<div class="card">
    <div class="card-body">
		<div id="smf-upload-files" class="h-500px border rounded p-3 mb-4 d-flex flex-column align-items-center justify-content-center" style="min-height: 65vh; position: relative;">
            <input type="file" id="fileInput" multiple hidden>
            <input type="file" id="folderInput" webkitdirectory multiple hidden>
			<input name="folder_id" type="hidden" value="{{$folder_id}}">
			<div id="dropArea" 
                class="w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 text-center border border-dashed rounded" 
                style="cursor: pointer; min-height: 300px; background-color: #f9f9f9;">
                
                <p class="mb-3 font-weight-semibold h5">
                    {{ translate('Drag & Drop files or folders here') }}
                </p>

                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-center mb-3">
                    <div class="form-group mb-0">
                        <label for="uploadType" class="d-block font-weight-medium text-muted mb-1">
                            <i class="fas fa-upload mr-1 text-primary"></i> {{ translate('Upload Type') }}
                        </label>
                        <select id="uploadType" class="custom-select custom-select w-auto">
                            <option value="file">{{ translate('File') }}</option>
                            <option value="folder">{{ translate('Folder') }}</option>
                        </select>
                    </div>
                </div>

                <p class="text-muted small">
                    {{ translate('Supported: files, folders, drag & drop, or browse manually.') }}
                </p>
                <div class="mt-4 mt-sm-0">
                    <button type="button" class="btn btn-primary btn mt-sm-4" id="browseBtn">
                        {{ translate('Browse') }}
                    </button>
                </div>
                
                
            </div>


			<div id="previewArea" class="w-100 mt-4 d-flex flex-wrap gap-3"></div>
            <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 text-center rounded">
                <div id="uploadProgressContainer" class="w-100 mt-3 d-none">
                    <div class="progress" style="height: 25px;">
                        <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%">
                            0%
                        </div>
                    </div>
                </div>
            </div>
            <button id="uploadBtn" class="btn btn-success mt-4" style="display: none;">{{ translate('Upload Files') }}</button>
            
		</div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');
    const browseBtn = document.getElementById('browseBtn');
    const previewArea = document.getElementById('previewArea');
    const uploadBtn = document.getElementById('uploadBtn');

    let selectedFiles = [];

    browseBtn.addEventListener('click', () => {
        const type = document.getElementById('uploadType').value;
        if (type === 'folder') {
            document.getElementById('folderInput').click();
        } else {
            document.getElementById('fileInput').click();
        }
    });


    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files, false); // Not a folder upload
    });

    folderInput.addEventListener('change', (e) => {
        handleFiles(e.target.files, true); // Folder upload
    });

    ['dragenter', 'dragover'].forEach(event => {
        dropArea.addEventListener(event, e => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.add('border-primary');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        dropArea.addEventListener(event, e => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.remove('border-primary');
        });
    });

    dropArea.addEventListener('drop', async e => {
        e.preventDefault();
        e.stopPropagation();
        dropArea.classList.remove('border-primary');

        const items = e.dataTransfer.items;
        const entries = [];

        for (let i = 0; i < items.length; i++) {
            const item = items[i].webkitGetAsEntry();
            if (item) {
                entries.push(item);
            }
        }

        for (const entry of entries) {
            await traverseFileTree(entry);
        }

        if (selectedFiles.length > 0) {
            uploadBtn.style.display = 'inline-block';
        }
    });

    async function traverseFileTree(item, path = "") {
        return new Promise((resolve) => {
            if (item.isFile) {
                item.file(file => {
                    file._isFolderUpload = true;
                    selectedFiles.push({
                        file: file,
                        relativePath: path + file.name
                    });

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const fileType = file.type.split('/')[0];
                        const fileCard = document.createElement('div');
                        fileCard.classList.add('border', 'p-2', 'rounded', 'text-center');
                        fileCard.style.width = '150px';

                        if (fileType === 'image') {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.classList.add('img-fluid', 'rounded', 'mb-2');
                            img.style.maxHeight = '100px';
                            fileCard.appendChild(img);
                        } else {
                            const icon = document.createElement('div');
                            icon.innerHTML = '<i class="fas fa-file-alt fa-2x mb-2"></i>';
                            fileCard.appendChild(icon);
                        }

                        fileCard.innerHTML += `
                            <p class="small text-truncate">${file.name}</p>
                            <p class="small text-muted">${Math.round(file.size / 1024)} KB</p>
                        `;
                        previewArea.appendChild(fileCard);
                    };
                    reader.readAsDataURL(file);

                    resolve();
                });
            } else if (item.isDirectory) {
                const dirReader = item.createReader();
                dirReader.readEntries(async entries => {
                    for (const entry of entries) {
                        await traverseFileTree(entry, path + item.name + "/");
                    }
                    resolve();
                });
            }
        });
    }

    function handleFiles(files, isFolderUpload = false) {
        [...files].forEach(file => {

            let relativePathT = file.webkitRelativePath;
            if (!isFolderUpload || !relativePathT || relativePathT === "." || relativePathT.trim() === "") {
                relativePathT = file.name;
            }
            selectedFiles.push({
                file: file,
                relativePath: relativePathT
            });

            const reader = new FileReader();
            reader.onload = function (e) {
                const fileType = file.type.split('/')[0];
                const fileCard = document.createElement('div');
                fileCard.classList.add('border', 'p-2', 'rounded', 'text-center');
                fileCard.style.width = '150px';

                if (fileType === 'image') {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('img-fluid', 'rounded', 'mb-2');
                    img.style.maxHeight = '100px';
                    fileCard.appendChild(img);
                } else {
                    const icon = document.createElement('div');
                    icon.innerHTML = '<i class="fas fa-file-alt fa-2x mb-2"></i>';
                    fileCard.appendChild(icon);
                }

                fileCard.innerHTML += `
                    <p class="small text-truncate">${file.name}</p>
                    <p class="small text-muted">${Math.round(file.size / 1024)} KB</p>
                `;
                previewArea.appendChild(fileCard);
            };
            reader.readAsDataURL(file);
        });

        if (selectedFiles.length > 0) {
            uploadBtn.style.display = 'inline-block';
        }
    }


    uploadBtn.addEventListener('click', function () {
    if (!selectedFiles.length) {
        notifyMe("error", "No files selected.");
        return;
    }

    const formData = new FormData();

    selectedFiles.forEach(fileObj => {
        formData.append('smf_file[]', fileObj.file);

        let path = fileObj.relativePath;
        if (!path || path === '.' || path.trim() === '') {
            path = fileObj.file.name;
        }
        formData.append('paths[]', path);
    });

    formData.append('folder_id', document.querySelector('input[name="folder_id"]').value);

    // Show loader and progress
    // document.getElementById('uploadLoader').classList.remove('d-none');
    document.getElementById('uploadProgressContainer').classList.remove('d-none');
    const progressBar = document.getElementById('uploadProgressBar');
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';
    uploadBtn.disabled = true;

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.innerText = percent + '%';
        }
    });

    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            // document.getElementById('uploadLoader').classList.add('d-none');
            uploadBtn.disabled = false;

            if (xhr.status >= 200 && xhr.status < 300) {
                notifyMe("success", "Files uploaded successfully!");
                selectedFiles = [];
                previewArea.innerHTML = '';
                uploadBtn.style.display = 'none';
                document.getElementById('uploadProgressContainer').classList.add('d-none');
            } else {
                notifyMe("error", "Upload failed!");
            }
        }
    };

    xhr.open("POST", "{{ route('admin.uploaded-files.upload') }}", true);
    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
    xhr.send(formData);
});



});
</script>
@endsection


    <style>
        #uploadLoader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 999;
        }
    </style>

