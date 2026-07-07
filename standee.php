<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examsys Standee Design - Print Ready</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary: #1e3a8a;
            --primary-light: #3b82f6;
            --dark: #0f172a;
            --light: #f8fafc;
            --accent: #eff6ff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 0;
        }

        /* Standee Dimensions: Standard 2.5ft x 6ft ratio scaled for web display */
        .standee-container {
            width: 800px;
            height: 1920px;
            background: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 12px solid var(--primary);
        }

        /* Top Abstract Decorative Header */
        .header-bg {
            background: linear-gradient(135deg, var(--primary) 0%, #0d1b3e 100%);
            padding: 80px 40px 60px 40px;
            text-align: center;
            position: relative;
            border-bottom: 8px solid var(--primary-light);
        }

        .header-bg::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 50px;
            background: white;
            clip-path: polygon(0 0, 100% 0, 100% 100%);
        }

        .logo-box {
            font-size: 56px;
            font-weight: 800;
            color: white;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .logo-box span {
            color: var(--primary-light);
        }

        .subtitle {
            color: #93c5fd;
            font-size: 20px;
            font-weight: 400;
            letter-spacing: 1px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* Content Area */
        .content {
            padding: 80px 50px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 70px;
        }

        /* Section Title styling */
        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .section-title h3 {
            font-size: 26px;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title .line {
            flex-grow: 1;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), transparent);
            border-radius: 2px;
        }

        /* Flow / Process Timeline */
        .flow-container {
            display: flex;
            flex-direction: column;
            gap: 35px;
            position: relative;
        }

        .flow-container::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 30px;
            width: 4px;
            height: calc(100% - 40px);
            background: var(--accent);
            z-index: 1;
        }

        .flow-step {
            display: flex;
            gap: 25px;
            position: relative;
            z-index: 2;
        }

        .step-number {
            width: 64px;
            height: 64px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: 700;
            border: 4px solid var(--accent);
            flex-shrink: 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .flow-step:nth-child(even) .step-number {
            background: var(--primary-light);
        }

        .step-content {
            background: var(--light);
            padding: 20px 25px;
            border-radius: 12px;
            border-left: 5px solid var(--primary);
            flex-grow: 1;
        }

        .flow-step:nth-child(even) .step-content {
            border-left-color: var(--primary-light);
        }

        .step-content h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .step-content p {
            font-size: 15px;
            color: var(--text-muted);
            line-weight: 1.6;
        }

        /* Visual representation container */
        .visual-display {
            background: var(--accent);
            border-radius: 16px;
            padding: 35px;
            border: 2px solid var(--border);
        }

        /* Mock Dashboard Elements */
        .mock-dashboard {
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 25px;
        }

        .mock-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .mock-card h5 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .mock-card .val {
            font-size: 40px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .mock-card p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .mock-chart {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .mock-chart h5 {
            font-size: 13px;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 15px;
        }

        /* Bottom Footer with Student Details */
        .footer-bg {
            background: linear-gradient(135deg, #0d1b3e 0%, var(--primary) 100%);
            padding: 50px 40px;
            color: white;
            position: relative;
            border-top: 6px solid var(--primary-light);
        }

        .footer-bg::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 0;
            width: 100%;
            height: 30px;
            background: white;
            clip-path: polygon(0 100%, 100% 0, 100% 100%);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 15px 20px;
            border-radius: 10px;
        }

        .detail-item span {
            display: block;
            font-size: 12px;
            color: #93c5fd;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .detail-item strong {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        /* Print Override rules for PDF export */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .standee-container {
                box-shadow: none;
                border: none;
                width: 100%;
                height: 100%;
                position: absolute;
                top: 0;
                left: 0;
            }
        }
    </style>
</head>
<body>

    <div class="standee-container">
        
        <!-- Header Section -->
        <div class="header-bg">
            <div class="logo-box">Exam<span>sys</span></div>
            <div class="subtitle">Automated Examination Management & Evaluation System</div>
        </div>

        <!-- Content Area -->
        <div class="content">
            
            <!-- FLOW OF THE PROJECT -->
            <div>
                <div class="section-title">
                    <h3>System Architecture Flow</h3>
                    <div class="line"></div>
                </div>
                
                <div class="flow-container">
                    
                    <div class="flow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Role-Based Access Control</h4>
                            <p>Students and teachers securely register profiles mapping designated roles and departments.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Batch Course Enrollment</h4>
                            <p>Faculty members bulk-enroll students in specific courses matching academic years with a single click.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Real-Time Marks & Estimation</h4>
                            <p>Teachers log internal/external marks. System processes grading scale guidelines and estimates grades on-the-fly.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Dynamic Performance Curve</h4>
                            <p>Once finalized, academic results are published instantly, generating SGPA/CGPA cards and progression graphs.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- VISUAL REPRESENTATION -->
            <div>
                <div class="section-title">
                    <h3>Visual Analytics Interface</h3>
                    <div class="line"></div>
                </div>
                
                <div class="visual-display">
                    <div class="mock-dashboard">
                        <!-- Student CGPA Card -->
                        <div class="mock-card">
                            <h5>Current CGPA</h5>
                            <div class="val">3.67</div>
                            <p>Student: Mueez Ahmed</p>
                        </div>
                        
                        <!-- Line Chart representing performance curve -->
                        <div class="mock-chart">
                            <h5>Academic Curve</h5>
                            <!-- Pure SVG representing Sem 1 (3.84) to Sem 2 (3.52) progression -->
                            <svg viewBox="0 0 200 100" width="100%" height="80" style="margin-top: 10px;">
                                <!-- Grid Lines -->
                                <line x1="20" y1="20" x2="180" y2="20" stroke="#f1f5f9" stroke-width="1" />
                                <line x1="20" y1="50" x2="180" y2="50" stroke="#f1f5f9" stroke-width="1" />
                                <line x1="20" y1="80" x2="180" y2="80" stroke="#f1f5f9" stroke-width="2" />
                                
                                <!-- Chart Line -->
                                <path d="M 50 30 L 150 60" fill="none" stroke="#1e3a8a" stroke-width="3" stroke-linecap="round" />
                                <path d="M 50 30 L 150 60 L 150 80 L 50 80 Z" fill="rgba(30, 58, 138, 0.08)" stroke="none" />
                                
                                <!-- Data Points -->
                                <circle cx="50" cy="30" r="4" fill="#3b82f6" stroke="white" stroke-width="1.5" />
                                <circle cx="150" cy="60" r="4" fill="#3b82f6" stroke="white" stroke-width="1.5" />
                                
                                <!-- Labels -->
                                <text x="50" y="93" font-size="8" text-anchor="middle" fill="#64748b" font-weight="600">Sem 1 (3.84)</text>
                                <text x="150" y="93" font-size="8" text-anchor="middle" fill="#64748b" font-weight="600">Sem 2 (3.52)</text>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Section -->
        <div class="footer-bg">
            <div class="details-grid">
                <div class="detail-item">
                    <span>Presented By</span>
                    <strong>Wishah Ahmed</strong>
                </div>
                <div class="detail-item">
                    <span>Roll Number</span>
                    <strong>M-BBbis-22_21</strong>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
