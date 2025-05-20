<div class="modal-content">
    <form id="aiz-upload-form" method="POST" action="{{ route('uploader.upload') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="folder_id" value="{{ request('folder_id') }}">
        <div class="modal-header">
            <h5 class="modal-title">{{ translate('Upload File') }}</h5>
            <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        </div>
        <div class="modal-body">
            <input type="file" name="smf_file[]" multiple required>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">{{ translate('Upload') }}</button>
        </div>
    </form>
</div>
