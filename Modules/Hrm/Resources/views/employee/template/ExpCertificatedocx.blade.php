@extends('hrm::layouts.contractheader')
@section('page-title')
    {{ __('Experience Certificate') }}
@endsection
@section('content')
    <div class="row justify-content-center">
        <div class="col-10">
            <div class="container">
                <div>
                    <div class="card mt-5" id="printTable">
                        <div class="col-xs-12 text-end">
                            <img  src="{{ !empty (get_file(dark_logo())) ? get_file(dark_logo()) :'WorkDo'}}" class="m-3" style="max-width: 150px;"/>
                        </div>
                        <div class="card-body" id="exportContent">
                            <div class="row invoice-title mt-2">
                                <p data-v-f2a183a6="">
                                <div>{!! $experience_certificate->content !!}</div>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @push('scripts')
        <script>
            function closeScript() {
                setTimeout(function() {
                    window.open(window.location, '_self').close();
                }, 1000);
            }
            $(window).on('load', function() {
                var filename = '{{ $employees->name }}';
                var element = 'exportContent';
                var preHtml =
                    "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Export HTML To Doc</title></head><body>";
                var postHtml = "</body></html>";
                var html = preHtml + document.getElementById(element).innerHTML + postHtml;

                var blob = new Blob(['\ufeff', html], {
                    type: 'application/msword'
                });

                // Specify link url
                var url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);

                // Specify file name
                filename = filename ? filename + '.doc' : 'document.doc';

                // Create download link element
                var downloadLink = document.createElement("a");

                document.body.appendChild(downloadLink);

                if (navigator.msSaveOrOpenBlob) {
                    navigator.msSaveOrOpenBlob(blob, filename);
                } else {
                    // Create a link to the file
                    downloadLink.href = url;

                    // Setting the file name
                    downloadLink.download = filename;

                    //triggering the function
                    downloadLink.click();
                }

                document.body.removeChild(downloadLink);

            });
        </script>
    @endpush
