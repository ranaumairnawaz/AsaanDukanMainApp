<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }} – Store Unavailable</title>
    <meta name="description" content="This store is currently unavailable. Please check back later.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fb;
            color: #111;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            background: #fee2e2;
            width: 7rem;
            height: 7rem;
            border-radius: 50%;
            line-height: 7rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #111;
        }

        p {
            font-size: 1.05rem;
            color: #555;
            max-width: 420px;
            line-height: 1.6;
        }

        .store-name {
            font-weight: 700;
            color: #111;
        }

        .footer {
            margin-top: 3.5rem;
            font-size: 0.75rem;
            color: #aaa;
        }

        .footer a {
            color: #1F7A4D;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .divider {
            width: 40px;
            height: 3px;
            background: #e2e8f0;
            border-radius: 99px;
            margin: 1.5rem auto;
        }
    </style>
</head>

<body>
    <div class="icon">🏪</div>
    <h1>Store Unavailable</h1>
    <div class="divider"></div>
    <p>
        <span class="store-name">{{ $tenant->name }}</span> is currently not available.
        The store may be under maintenance or the subscription has lapsed.
        Please check back later or contact the store owner directly.
    </p>

    <div class="footer">
        Powered by <a href="/">AssanDukan</a> &mdash; Pakistan's Online Store Builder
    </div>
</body>

</html>