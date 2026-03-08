.admin-content {
    padding: 30px;
    background: #f7f9fc;
    min-height: 100vh;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.admin-header h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    margin: 0;
    color: #2c3e50;
}

.card-custom {
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
    overflow: hidden;
}

.form-control-custom {
    border-radius: 10px;
    min-height: 44px;
}

.btn-primary-custom {
    background: #007bff;
    border: none;
    color: #fff;
    border-radius: 10px;
    padding: 10px 18px;
}

.btn-primary-custom:hover {
    background: #0069d9;
    color: #fff;
}

.btn-secondary-custom {
    background: #6c757d;
    border: none;
    color: #fff;
    border-radius: 10px;
    padding: 10px 18px;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    color: #fff;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    font-weight: 600;
    color: #007bff;
    text-decoration: none;
}

.back-link:hover {
    text-decoration: underline;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table thead {
    background: #f1f3f5;
}

.admin-table th,
.admin-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
}

.small-empty {
    padding: 30px 20px;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 700;
    text-transform: capitalize;
}

.status-waiting {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.application-box {
    background: #fff;
}

.modal-custom .modal-content {
    border-radius: 16px;
    border: none;
}