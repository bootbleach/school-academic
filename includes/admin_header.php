<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Metta Academic'; ?> - Metta Academic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px; background-color: #2c3e50;
            transition: margin-left 0.3s ease;
        }
        .sidebar .list-group-item {
            background-color: transparent; color: #ecf0f1; border: none;
            border-left: 4px solid transparent; padding: 1rem 1.25rem; transition: all 0.3s ease;
        }
        .sidebar .list-group-item:hover, .sidebar .list-group-item.active {
            background-color: #34495e; color: #ffffff; border-left-color: #3498db;
        }
        .content-wrapper { flex-grow: 1; }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed; top: 0; bottom: 0; left: 0; z-index: 1050;
                margin-left: -280px;
            }
            .sidebar.toggled { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">