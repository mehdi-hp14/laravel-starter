<!doctype html>
<html lang="en" dir="rtl">

<head>
    <meta charset="utf-8"/>
    <title>{{ 'پنل کاربری | ' . trans($title ?? '') }}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>

    <!-- END GLOBAL MANDATORY STYLES -->
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    @yield('page_level_stylesheets')
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{csrf_token()}}">

    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @mix('css', '/css/app.css', '/resources/customer')
</head>
<body>
@yield('content')
@yield('pre_scripts')
@mix('js', '/js/app.js', '/resources/customer')
@yield('later_scripts')
</body>
</html>
