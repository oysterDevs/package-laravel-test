@extends('admin.layouts.master')

@section('title')
	File Manager
@endsection

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('File Manager')}}</h1>
		</div>
		<div class="col-md-6 text-md-right">
			<a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createFolderModal">
				{{ translate('Create Folder') }}
			</a>
			@can('uploaded_files_create')
				<a href="{{ route('admin.uploaded-files.create', $current_folder_id ?? 0) }}" class="btn btn-primary">
					<span>{{translate('Upload New File')}}</span>
				</a>
			@endcan
			@if($current_folder_id)
				@php
					$parent_id = \App\Models\UploadFolder::find($current_folder_id)->parent_id;
				@endphp
				<a href="{{ route('admin.uploaded-files.index', ['folder_id' => $parent_id ?? null]) }}" class="btn btn-secondary">
					← {{ translate($parent_id ? 'Back' : 'Back to Root') }}
				</a>
			@endif
		</div>
	</div>
</div>


<div class="row">
    {{-- Folders --}}
    @foreach($folders as $folder)
	<div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-4">
	<div class="card card-file text-center p-3 aiz-uploader-select c-default position-relative h-100">
		{{-- Dropdown --}}
		<div class="dropdown position-absolute" style="top: 8px; right: 8px;" onclick="event.stopPropagation();">
			<a class="dropdown-toggle-icon px-1 py-0 text-muted bg-gray border rounded d-inline-block"
			   data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
				<i class="fas fa-ellipsis-v"></i>
			</a>
			<div class="dropdown-menu dropdown-menu-right">
				<a href="javascript:void(0)" class="dropdown-item" onclick="renameFolder(this)" data-id="{{ $folder->id }}" data-name="{{ $folder->name }}">
					<i class="fas fa-edit mr-2"></i> Rename
				</a>
				<a href="{{ route('admin.uploaded-files.download.folder', $folder->id) }}" class="dropdown-item">
					<i class="fas fa-download mr-2"></i>Download
				</a>
				@can('uploaded_files_delete')
					<!-- Trigger delete modal for folder -->
					<a href="javascript:void(0)" class="dropdown-item text-danger" 
					   onclick="showDeleteModal('{{ route('uploaded-files.delete-folder', $folder->id) }}')">
						<i class="fas fa-trash mr-2"></i>{{ translate('Delete') }}
					</a>
				@endcan
			</div>
		</div>

		{{-- Folder link (no stretched-link) --}}
		<a href="{{ route('admin.uploaded-files.index', ['folder_id' => $folder->id]) }}"
		   class="text-decoration-none text-dark d-block h-100 pt-4">
			<div class="card-file-thumb text-center mb-3">
				<i class="fas fa-folder fa-5x text-gray my-4 d-block mx-auto"></i>
			</div>
			<div class="card-body p-1">
				<div class="text-wrap text-bold"
				     style="font-size: 0.95rem; line-height: 1.1rem; word-break: break-word; max-height: 4.5rem; overflow: hidden;">
					{{ $folder->name }}
				</div>
			</div>
		</a>
	</div>
</div>


    @endforeach

    {{-- Files --}}
    @foreach($all_uploads as $file)
        @php $file_name = $file->file_original_name ?? translate('Unknown'); @endphp
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-4">
            <div class="card card-file aiz-uploader-select c-default position-relative p-2 text-center h-100" title="{{ $file_name }}.{{ $file->extension }}">
                
                {{-- Dropdown --}}
                <div class="dropdown position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                    <a class="px-1 py-0 text-muted bg-gray border rounded" data-toggle="dropdown" role="button">
                        <i class="fas fa-ellipsis-v" style="font-size:12px"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="javascript:void(0)" class="dropdown-item" onclick="detailsInfo(this)" data-id="{{ $file->id }}">
                            <i class="fas fa-info-circle mr-2"></i>{{ translate('Details Info') }}
                        </a>
                        <a href="{{ uploaded_asset($file->file_name) }}" target="_blank" download="{{ $file_name }}.{{ $file->extension }}" class="dropdown-item">
                            <i class="fas fa-download mr-2"></i>{{ translate('Download') }}
                        </a>
                        <a href="javascript:void(0)" class="dropdown-item" onclick="copyUrl(this)" data-url="{{ uploaded_asset($file->file_name) }}">
                            <i class="fas fa-clipboard mr-2"></i>{{ translate('Copy Link') }}
                        </a>
                        @can('uploaded_files_delete')
                            <a href="#" class="dropdown-item text-danger confirm-delete" data-href="{{ route('uploaded-files.destroy', $file->id ) }}">
                                <i class="fas fa-trash mr-2"></i>{{ translate('Delete') }}
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- File Preview --}}
                <div class="card-file-thumb mb-2">
					@if($file->type == 'image')
						<img src="{{ uploaded_asset($file->file_name) }}" class="img-fit w-100 rounded" style="max-height: 150px; object-fit: cover;">
					@elseif($file->type == 'video')
						<i class="fas fa-file-video fa-5x text-primary my-4 d-block mx-auto"></i>
					@elseif($file->extension == 'pdf')
						<i class="fas fa-file-pdf fa-5x text-danger my-4 d-block mx-auto"></i>
					@elseif(in_array($file->extension, ['xls', 'xlsx', 'csv']))
						<i class="fas fa-file-excel fa-5x text-success my-4 d-block mx-auto"></i>
					@elseif(in_array($file->extension, ['doc', 'docx']))
						<i class="fas fa-file-word fa-5x text-info my-4 d-block mx-auto"></i>
					@else
						<i class="fas fa-file fa-5x text-secondary my-4 d-block mx-auto"></i>
					@endif
                </div>

                {{-- File Info --}}
                <div class="card-body p-1">
                    <h6 class="d-block text-truncate mb-1" style="font-size: 0.9rem;">
                        {{ $file_name }}.<span class="ext">{{ $file->extension }}</span>
                    </h6>
                    <p class="mb-0 small" style="font-size: 0.75rem;">{{ formatBytes($file->file_size) }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>


{{-- Pagination --}}
<div class="aiz-pagination">
    {{ $all_uploads->links() }}
</div>
<!-- Delete Confirmation Modal -->

<!-- delete Modal -->
<div id="deleteModal" class="modal fade">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6">{{translate('Delete Confirmation')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"> <i
                        class="fas fa-times text-sm"></i> </button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-1">Are you sure to delete this Folder?</p>
            </div>
			<div class="modal-footer">
                <form id="delete-modal-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div><!-- /.modal -->

{{-- Create Folder Modal --}}
<div class="modal fade" id="createFolderModal" tabindex="-1" role="dialog">
    <form action="{{ route('uploaded-files.create-folder') }}" method="POST">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $current_folder_id }}">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Create Folder') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" class="form-control" placeholder="{{ translate('Folder Name') }}" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ translate('Create') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal fade" id="aizUploaderModal" tabindex="-1" role="dialog" aria-hidden="true">
</div>

<!-- Rename Folder Modal -->
<div class="modal fade" id="renameFolderModal" tabindex="-1" role="dialog">
    <form method="POST" id="renameFolderForm">
        @csrf
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Folder</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" id="renameFolderName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="submitRenameFolder()">Rename</button>
                </div>
            </div>
        </div>
    </form>
</div>


@endsection

@section('modal')
	@include('admin.modals.delete_modal')

	<div id="info-modal" class="modal fade">
		<div class="modal-dialog modal-dialog-right">
				<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title h6">{{ translate('File Info') }}</h5>
					<button type="button" class="close" data-dismiss="modal">
					</button>
				</div>
				<div class="modal-body c-scrollbar-light position-relative" id="info-modal-content">
					<div class="c-preloader text-center absolute-center">
						<i class="fas fa-spinner la-spin la-3x opacity-70"></i>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('script')
<script type="text/javascript">
    function showUploaderModal(folderId) {
        $.get('{{ route("uploader.show") }}', { folder_id: folderId }, function(data) {
            $('#aizUploaderModal').html(data);
            $('#aizUploaderModal').modal('show');
        });
    }

	function detailsInfo(e) {
			let id = $(e).data('id');
			
			// Show loading spinner
			$('#info-modal-content').html('<div class="c-preloader text-center absolute-center"><i class="las la-spinner la-spin la-3x opacity-70"></i></div>');
			$('#info-modal').modal('show');

			// Get CSRF token from meta
			const csrfToken = $('meta[name="csrf-token"]').attr('content');

			// Perform AJAX call
			$.ajax({
				url: '{{ route("uploaded-files.info") }}',
				type: 'POST',
				data: {
					_token: '{{ csrf_token() }}',
					id: id
				},
				success: function (data) {
					$('#info-modal-content').html(data);
				},
				error: function (xhr) {
					$('#info-modal-content').html('<div class="text-danger p-3">Failed to load info.</div>');
				}
			});
		}
		function copyUrl(e) {
			var url = $(e).data('url');
			var $temp = $("<input>");
		    $("body").append($temp);
		    $temp.val(url).select();
		    try {
			    document.execCommand("copy");
			    notifyMe('success', '{{ translate('Link copied to clipboard') }}');
			} catch (err) {
			    notifyMe('danger', '{{ translate('Oops, unable to copy') }}');
			}
		    $temp.remove();
		}

		

		$(document).ready(function () {
			$('.dropdown-toggle-icon').dropdown(); // initialize manually
		});

		$(document).on('click', '[data-toggle="dropdown"]', function (event) {
			event.stopPropagation(); // Prevent dropdown from closing immediately
		});

		function showDeleteModal(deleteUrl) {
			console.log("fawfa",deleteUrl);
			
			// Set the delete URL in the modal form
			$('#delete-modal-form').attr('action', deleteUrl);
			
			// Show the modal
			$('#deleteModal').modal('show');
		}

		function renameFolder(e) {
			// Get folder data from the clicked element
			const folderId = $(e).data('id');
			const folderName = $(e).data('name');
			
			// Set current folder name in the input field
			$('#renameFolderName').val(folderName);
			
			// Set folder ID in the form (for later AJAX request)
			$('#renameFolderForm').data('folder-id', folderId);

			// Show the rename modal
			$('#renameFolderModal').modal('show');
		}

		function submitRenameFolder() {
			const newName = $('#renameFolderName').val();  // Get the new folder name
			const folderId = $('#renameFolderForm').data('folder-id');  // Get the folder ID
			
			// Show loading spinner in the modal content area
			$('#renameFolderModal .modal-body').append('<div class="c-preloader text-center"><i class="las la-spinner la-spin la-3x"></i></div>');

			// Perform AJAX to rename folder
			$.ajax({
				url: '{{ route("uploaded-files.rename-folder") }}',
				method: 'POST',
				data: {
					_token: '{{ csrf_token() }}',
					id: folderId,
					name: newName
				},
				success: function (response) {
					// Hide the loading spinner
					$('#renameFolderModal .c-preloader').remove();

					   // Reload the page after renaming the folder
    					window.location.reload();

					// Close the modal
					$('#renameFolderModal').modal('hide');
				},
				error: function (xhr) {
					// Handle error if the renaming fails
					$('#renameFolderModal .c-preloader').remove();
					alert('Failed to rename folder');
				}
			});
		}

    

</script>
@endsection
