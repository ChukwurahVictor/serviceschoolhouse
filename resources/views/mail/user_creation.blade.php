@extends('mail.layout')

@section('content')
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Hi {{ $details['name'] }},</p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Welcome to Service School House! We are excited to have you on board. We are striving towards a mission to enable every Customer Service professional to learn without limits.
  </p>
  
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    Here are 3 things you can do now to get started:
    <ol>
      <li>Take your first course.</li>
      <li>View the courses you are enrolled in . Click on 'View Course' and begin learning.</li>
      <li>Complete a course and download your certificate.</li>
    </ol>
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    Your credentials are:
    <ol>
      <li>Email: {{ $details['email'] }}</li>
      <li>Password: {{ $details['password'] }}</li>
    </ol>
  </p>

  <center>
    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
      
      <a href="{{ $details['login_link'] }}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; padding: 12px 25px; text-transform: capitalize; border-color: #3498db; margin:auto">Login here</a> 
    </p>
  </center>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    We are thrilled that you are enrolled. That's it for now....more insights soon!
  </p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    Keep learning, keep growing. <br>
    Explore, Learn and Grow.
  </p>
@endsection

                      