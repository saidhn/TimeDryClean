<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 3em;
            color: #333;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            /* Blue color */
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
        }

        .emoji {
            font-size: 4em;
            /* Make the emoji larger */
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="emoji"> 😞 </div>
        <h1>419</h1>
        <p>{{__('messages.419_page_expired')}}</p>
        <a href="/" class="btn">{{ __('messages.go_homepage') }}</a>
    </div>
</body>

</html>