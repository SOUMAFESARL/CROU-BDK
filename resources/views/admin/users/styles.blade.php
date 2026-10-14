{{-- resources/views/admin/users/_styles.blade.php --}}
<style>
    .form-container {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group { margin-bottom: 15px; }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #374151;
    }

    .form-group .required { color: #ef4444; }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="date"],
    .form-group input[type="password"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: var(--app-font-min, 15px);
        transition: border-color .3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    .form-group input.is-invalid,
    .form-group select.is-invalid {
        border-color: #ef4444;
    }

    .upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .3s;
        background-color: #f9fafb;
    }

    .upload-area:hover {
        border-color: #3b82f6;
        background-color: #f0f9ff;
    }

    .upload-icon { font-size: 32px; margin-bottom: 10px; opacity: .7; }
    .upload-text  { color: #6b7280; font-size: var(--app-font-min, 15px); }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .btn {
        padding: 10px 22px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all .3s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary          { background-color: #3b82f6; color: white; }
    .btn-primary:hover    { background-color: #2563eb; color: white; }
    .btn-secondary        { background-color: #6b7280; color: white; }
    .btn-secondary:hover  { background-color: #4b5563; color: white; }
    .btn-outline-secondary { background: transparent; border: 1px solid #6b7280; color: #6b7280; }
    .btn-outline-secondary:hover { background-color: #6b7280; color: white; }
    .btn-danger           { background-color: #ef4444; color: white; }
    .btn-danger:hover     { background-color: #dc2626; color: white; }

    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
    }
</style>