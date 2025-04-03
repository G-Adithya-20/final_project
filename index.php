<?php
session_start();
require 'includes/db_connect.php';

// Fetch stats from database
$totalTeamsQuery = "SELECT COUNT(*) as total_teams FROM teams";
$totalTeamsResult = $conn->query($totalTeamsQuery);
$totalTeams = $totalTeamsResult->fetch_assoc()['total_teams'];

$totalProjectsQuery = "SELECT COUNT(*) as total_projects FROM projects";
$totalProjectsResult = $conn->query($totalProjectsQuery);
$totalProjects = $totalProjectsResult->fetch_assoc()['total_projects'];

$totalTasksQuery = "SELECT COUNT(*) as total_tasks FROM tasks";
$totalTasksResult = $conn->query($totalTasksQuery);
$totalTasks = $totalTasksResult->fetch_assoc()['total_tasks'];

$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total_users'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hive - Online Work Collaboration Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    
    <style>
        :root {
            --primary: #3a0ca3;
            --secondary: #4361ee;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background-color: var(--light);
        }
        
        /* Navbar Styling */
        .navbar {
            padding: 1.2rem 0;
            transition: all 0.4s ease;
            background: transparent;
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
        }
        
        .navbar.scrolled .navbar-brand,
        .navbar.scrolled .nav-link {
            color: var(--dark) !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: white !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            background: var(--accent);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.5);
        }
        
        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 15px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:after {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background: var(--accent);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover:after {
            width: 100%;
        }
        
        .login-btn {
            background: white;
            color: var(--primary) !important;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.25);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 8rem 0 12rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-text {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .cta-button {
            background: white;
            color: var(--primary);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-button i {
            margin-left: 10px;
            transition: transform 0.3s ease;
        }
        
        .cta-button:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .cta-button:hover i {
            transform: translateX(5px);
        }
        
        .hero-shape {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
        }
        
        .hero-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }
        
        .hero-image {
            position: relative;
            z-index: 2;
        }
        
        .hero-image img {
            max-width: 100%;
            transform-style: preserve-3d;
            animation: levitate 6s ease-in-out infinite;
        }
        
        @keyframes levitate {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        /* Stats Section */
        .stats-section {
            margin-top: -100px;
            position: relative;
            z-index: 10;
            padding-bottom: 4rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover * {
            color: white !important;
            position: relative;
            z-index: 1;
        }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.4s ease;
        }
        
        .stat-card:hover .stat-icon {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stat-icon i {
            font-size: 2rem;
            color: var(--secondary);
            transition: all 0.4s ease;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            transition: all 0.4s ease;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card:hover .stat-number {
            -webkit-text-fill-color: white;
        }
        
        .stat-title {
            color: var(--dark);
            font-size: 1.2rem;
            font-weight: 500;
            margin: 0;
            transition: all 0.4s ease;
        }
        
        /* Features Section */
        .features-section {
            padding: 8rem 0;
            background: var(--light);
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 5rem;
        }
        
        .section-title h2 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--gradient);
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .section-title p {
            color: #6c757d;
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            top: 0;
            left: 0;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-card:hover * {
            color: white !important;
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            transition: all 0.4s ease;
        }
        
        .feature-card:hover .feature-icon {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .feature-icon i {
            font-size: 2.5rem;
            color: var(--secondary);
            transition: all 0.4s ease;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            transition: all 0.4s ease;
        }
        
        .feature-text {
            color: #6c757d;
            line-height: 1.8;
            transition: all 0.4s ease;
        }
        
        /* Testimonial Section */
        .testimonial-section {
            background: var(--gradient);
            padding: 8rem 0;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-shape {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.05;
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0 15px;
        }
        
        .testimonial-text {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            background: white;
        }
        
        .author-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .author-info h5 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .author-info p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 8rem 0;
            background: white;
            text-align: center;
        }
        
        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }
        
        .cta-text {
            font-size: 1.25rem;
            color: #6c757d;
            margin-bottom: 3rem;
        }
        
        /* Footer */
        footer {
            background: #1a1a2e;
            color: white;
            padding: 5rem 0 2rem;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .footer-logo i {
            color: var(--accent);
            margin-right: 10px;
        }
        
        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            max-width: 300px;
        }
        
        .footer-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 2px;
            background: var(--accent);
            bottom: 0;
            left: 0;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .footer-links a i {
            margin-right: 10px;
            font-size: 0.8rem;
        }
        
        .footer-links a:hover {
            color: var(--accent);
            transform: translateX(5px);
        }
        
        .social-links {
            display: flex;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-5px);
        }
        
        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 50px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.95);
                padding: 20px;
                border-radius: 10px;
                margin-top: 15px;
            }
            
            .navbar-collapse .nav-link {
                color: var(--dark) !important;
            }
            
            .hero-image {
                margin-top: 3rem;
            }
            
            .stat-card {
                margin-bottom: 30px;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 6rem 0 10rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title h2 {
                font-size: 2.5rem;
            }
            
            .cta-title {
                font-size: 2.5rem;
            }
        }
        
        /* Count animation */
        .stat-number {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        
        .count-animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Glow effect */
        .glow {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(67, 97, 238, 0.3) 0%, rgba(67, 97, 238, 0) 70%);
            z-index: -1;
            animation: pulse 4s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.5);
                opacity: 0.3;
            }
            100% {
                transform: scale(1);
                opacity: 0.5;
            }
        }

        .hero-illustration {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .animated-svg {
            width: 100%;
            max-width: 600px;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.15));
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users"></i>
               Hive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                



                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Animated particles -->
        <div class="hero-particles" id="particles-js"></div>
        
        <!-- Glowing effects -->
        <div class="glow" style="top: 20%; left: 15%;"></div>
        <div class="glow" style="top: 60%; left: 80%;"></div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="hero-title">Transform Your Team Collaboration</h1>
                    <p class="hero-text">Experience the future of remote work with our innovative platform. Streamline communication, boost productivity, and achieve more together.</p>
                    <a href="pages/login.php" class="cta-button">
                        Get Started Today<i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="col-lg-6 hero-image" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <div class="hero-illustration">
                        <svg viewBox="0 0 600 500" class="animated-svg">
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:var(--primary);stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:var(--secondary);stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path fill="url(#grad)" d="M50,250c0-110.5,89.5-200,200-200s200,89.5,200,200s-89.5,200-200,200S50,360.5,50,250">
                                <animate attributeName="d" dur="10s" repeatCount="indefinite" 
                                    values="M50,250c0-110.5,89.5-200,200-200s200,89.5,200,200s-89.5,200-200,200S50,360.5,50,250;
                                            M50,250c0-110.5,110.5-180,220-180s180,89.5,180,200s-89.5,220-200,220S50,360.5,50,250;
                                            M50,250c0-110.5,89.5-200,200-200s200,89.5,200,200s-89.5,200-200,200S50,360.5,50,250"/>
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Wave shape divider -->
        <div class="hero-shape">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#f8f9fa" fill-opacity="1" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,170.7C960,160,1056,192,1152,197.3C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-duration="800">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stat-number" data-value="<?php echo $totalTeams; ?>"><?php echo $totalTeams; ?></h3>
                                <p class="stat-title">Active Teams</p>
                            </div> 
                        </div>
                        <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <h3 class="stat-number" data-value="<?php echo $totalProjects; ?>"><?php echo $totalProjects; ?></h3>
                                <p class="stat-title">Total Projects</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h3 class="stat-number" data-value="<?php echo $totalTasks; ?>"><?php echo $totalTasks; ?></h3>
                                <p class="stat-title">Tasks</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <h3 class="stat-number" data-value="<?php echo $totalUsers; ?>"><?php echo $totalUsers; ?></h3>
                                <p class="stat-title">Total Users</p>
                            </div>
                        </div>
                    </div>
                </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Platform Features</h2>
                <p>Everything you need for effective team collaboration</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Team Collaboration</h3>
                        <p class="feature-text">Secure team-specific discussions and file sharing with version control system. Isolated team spaces for focused collaboration.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="feature-title">Task Management</h3>
                        <p class="feature-text">Comprehensive task tracking with progress monitoring and point-based rewards. Manager oversight for project timeline control.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="feature-title">Virtual Meetings</h3>
                        <p class="feature-text">Integrated Google Meet platform with customizable participation settings and manager notification system.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Analytics Dashboard</h3>
                        <p class="feature-text">Real-time analytics to track team performance, project progress, and individual contributions with customizable reports.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Enhanced Security</h3>
                        <p class="feature-text">Enterprise-grade security with role-based access control and end-to-end encryption.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-link"></i>
    </div>
     <h3 class="feature-title">Integrations</h3>
                        <p class="feature-text">Seamless integration with your favorite tools like Github , Google meet, and many more productivity apps.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="testimonial-section" id="testimonials">
        <div class="testimonial-shape">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <path fill="#FFFFFF" d="M55.2,-63.2C69.2,-51.4,76.9,-31.6,79.5,-11.4C82.1,8.9,79.5,29.5,68.8,44C58.1,58.5,39.3,66.7,19.7,72.1C0.1,77.5,-20.4,80,-36.3,72C-52.2,64,-63.6,45.5,-70.1,25.6C-76.5,5.7,-78,-15.5,-70.4,-31.8C-62.7,-48.1,-45.9,-59.5,-29.5,-70.5C-13.1,-81.6,2.9,-92.2,19.9,-88.6C36.9,-85,55,-74.9,55.2,-63.2Z" transform="translate(100 100)" />
            </svg>
        </div>
        
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2 style="color: white;">What Our Users Say</h2>
                <p style="color: rgba(255,255,255,0.8);">Trusted by teams around the world</p>
            </div>
            
            <div class="row" data-aos="fade-up" data-aos-delay="200">
                <div class="col-12">
                    <div class="testimonial-slider">
                        <div class="testimonial-card">
                            <div class="testimonial-text">
                                "Hive has revolutionized how our remote team operates. The task management features and integrated meeting tools have increased our productivity by 40% in just two months."
                            </div>
                            <div class="testimonial-author">
                                <div class="author-image">
                                    <img src="https://media.istockphoto.com/id/1364387823/photo/cheerful-young-woman-taking-a-selfie-next-to-the-sea.jpg?s=612x612&w=0&k=20&c=4OO6vNaL6yc_yQ7pHLVnP9mApO3RsxpJm9A5DpnEmlQ=" alt="User testimonial" loading="lazy">
                                </div>
                                <div class="author-info">
                                    <h5>Sarah Johnson</h5>
                                    <p>Project Manager, TechSolutions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <h2 class="cta-title">Ready to Transform Your Teamwork?</h2>
                <p class="cta-text">Join thousands of teams around the world already using Hive to achieve more together.</p>
                
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">
                        <i class="fas fa-users"></i> Hive
                    </div>
                    <p class="footer-text">Building the future of work, together. Our platform helps teams collaborate effectively no matter where they are.</p>
        
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="aboutus.html"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    </ul>
                </div>
                
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Support</h4>
                    <ul class="footer-links">
                        <li><a href="contactus.html"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
                
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 Hive. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            once: true,
            duration: 1000
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Generate random particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles-js');
            const particlesCount = 15;
            
            for (let i = 0; i < particlesCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random position
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                
                // Random size
                const size = Math.random() * 30 + 10;
                
                // Random animation duration
                const duration = Math.random() * 20 + 10;
                
                // Random animation delay
                const delay = Math.random() * 10;
                
                // Set styles
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Stats counter animation
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Observer for stats section
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.5
        };
        
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statValue = entry.target;
                    statValue.classList.add('count-animated');
                    const finalValue = parseInt(statValue.getAttribute('data-value'));
                    animateValue(statValue, 0, finalValue, 2000);
                    statsObserver.unobserve(statValue);
                }
            });
        }, observerOptions);
        
        // Observe all stat numbers
        document.addEventListener('DOMContentLoaded', function() {
            // Create particles
            createParticles();
            
            // Initialize counter animations
            document.querySelectorAll('.stat-number').forEach(stat => {
                statsObserver.observe(stat);
            });
        });
    </script>
</body>
</html>