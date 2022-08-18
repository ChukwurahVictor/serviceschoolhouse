@extends('mail.layout')

@section('content')
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Hi {{ $details['name'] }},</p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">You have been enrolled in the Customer Service Course at on Service School by Access Bank's HR team. Your courses are now available on your {{ $details['website_link'] }} dashboard.</p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    To access your courses, please follow these instructions:
    <ol>
      <li>Click this to navigate to course: <a href="{{ $details['login_link'] }}"><strong>Login</strong></a></li>
      <li>Your username is: {{ $details['email'] }}</li>
      <!--<li>Your password is {password}</li>-->
    </ol>
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    If you have any questions or concerns about the course, please contact 
    <a href="mailto:customercare@serviceschoolhouse.com">customercare@serviceschoolhouse.com</a>
     or click on the live chat button at the bottom right hand side of the <a href="{{ $details['website_link'] }}">website</a>.
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    Kind Regards.
  </p>
@endsection

                      