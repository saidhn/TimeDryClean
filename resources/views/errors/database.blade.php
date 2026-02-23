<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Unavailable</title>
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
            max-width: 600px;
        }

        h1 {
            font-size: 3em;
            color: #333;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 1.4em;
            color: #555;
            margin-bottom: 20px;
            font-weight: normal;
        }

        p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin: 5px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .emoji {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .detail-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            text-align: left;
            word-break: break-word;
        }

        .detail-box strong {
            color: #856404;
        }

        .detail-box code {
            font-size: 0.85em;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="emoji">🔌</div>
        <h1>503</h1>
        <h2>Service Temporarily Unavailable</h2>
        <p>We're having trouble connecting to our database. This is usually temporary — please try again in a few moments.</p>

        @if($detail)
        <div class="detail-box">
            <strong>Error detail:</strong><br>
            <code>{{ $detail }}</code>
        </div>
        @endif

        <div style="margin-top: 30px;">
            <a href="javascript:location.reload()" class="btn">Retry</a>
            <a href="/" class="btn btn-secondary">Go Homepage</a>
        </div>
    </div>
</body>

</html>
