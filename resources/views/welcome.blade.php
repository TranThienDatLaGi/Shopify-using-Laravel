@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        /* Hiệu ứng fade-in cho toàn trang */
        .dashboard-container {
            animation: fadeIn 0.8s ease-in-out;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card chính giữa */
        .dashboard-card {
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            padding: 40px 50px;
            text-align: center;
            max-width: 600px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        /* Tiêu đề chính */
        .dashboard-card h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }

        /* Mô tả phụ */
        .dashboard-card p {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 25px;
        }

        /* Nút hiệu ứng */
        .dashboard-card .btn-primary {
            padding: 10px 25px;
            font-size: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .dashboard-card .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        /* Hiệu ứng icon */
        .emoji {
            font-size: 2.5rem;
            animation: bounce 1.5s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }
    </style>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="emoji mb-3">🚀</div>
            <h1>Chào mừng bạn đến với Shopify App!</h1>
            <p>Đây là trang Dashboard — nơi bạn có thể quản lý ứng dụng của mình.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Bắt đầu ngay</a>
        </div>
    </div>
@endsection