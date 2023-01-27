@extends('mail.layout')

@section('image')
  <img src="{{$details['image'] ? $details['image'] : 'https://serviceschoolhouse.com/static/media/logo_new.d1db4956.png'
 }}" title="Logo" width="150px" height="auto" style="margin-top: 1rem;" />
@endsection

@section('content')
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Hi </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">You have been enrolled in the Customer Service Course at on Service School by Access Bank's HR team. Your courses are now available on your  dashboard.</p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    To access your courses, please follow these instructions:
    <ol>
      @foreach($details['courses'] as $ru)
      
          <li>{{ $ru }}</li>
      
      @endforeach
    </ol>
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    If you have any questions or concerns about the course, please contact 
    <a href="mailto:customercare@serviceschoolhouse.com">customercare@serviceschoolhouse.com</a>
     or click on the live chat button at the bottom right hand side of the <a href="">website</a>.
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    Kind Regards.
  </p>
@endsection