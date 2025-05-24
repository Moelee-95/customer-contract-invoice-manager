<?php
// Start the session
session_start();

// Check if user is NOT logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management Dashboard</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="dashboard_style.css">
    <link rel="stylesheet" href="navbar_style.css">

</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            Customer Management System
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="username">Welcome, <?php echo htmlspecialchars($username); ?></div>
                <div class="user-role">User ID: <?php echo htmlspecialchars($user_id); ?></div>
            </div>
            <a href="?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                Logout
            </a>
        </div>
    </nav>

    <div id="app">
        <div class="container">
            <div class="nav-tabs">
                <button class="nav-tab" :class="{active: activeTab === 'customers'}" @click="activeTab = 'customers'">
                    Customers
                </button>
                <button class="nav-tab" :class="{active: activeTab === 'contracts'}" @click="activeTab = 'contracts'">
                    Contracts
                </button>
                <button class="nav-tab" :class="{active: activeTab === 'invoices'}" @click="activeTab = 'invoices'">
                    Invoices
                </button>
                <button class="nav-tab" :class="{active: activeTab === 'logs'}" @click="activeTab = 'logs'">
                    User Actions
                </button>
            </div>

            <!-- Customers Section -->
            <div v-if="activeTab === 'customers'" class="content-section">
                <div class="section-header">
                    <h2>Customers</h2>
                    <button class="btn btn-primary" @click="showCustomerModal = true; editingCustomer = null">
                        Add Customer
                    </button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Free Trial</th>
                                <th>Start Date</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in customers" :key="customer.id">
                                <td>{{ customer.name }}</td>
                                <td>{{ customer.address }}</td>
                                <td>{{ customer.phone }}</td>
                                <td>{{ customer.freeTrial }}</td>
                                <td>{{ customer.startDate }}</td>
                                <td>{{ getUserName(customer.createdBy) }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" @click="editCustomer(customer)">Edit</button>
                                    <button class="btn btn-danger btn-sm" @click="deleteCustomer(customer.id)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contracts Section -->
            <div v-if="activeTab === 'contracts'" class="content-section">
                <div class="section-header">
                    <h2>Contracts</h2>
                    <button class="btn btn-primary" @click="showContractModal = true; editingContract = null">
                        Add Contract
                    </button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Start Date</th>
                                <th>Expire Date</th>
                                <th>Payment</th>
                                <th>Note</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="contract in contracts" :key="contract.id">
                                <td>{{ getCustomerName(contract.customerId) }}</td>
                                <td>{{ contract.startDate }}</td>
                                <td>{{ contract.expireDate }}</td>
                                <td>${{ contract.payment }}</td>
                                <td>{{ contract.note }}</td>
                                <td>{{ getUserName(contract.createdBy) }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" @click="editContract(contract)">Edit</button>
                                    <button class="btn btn-danger btn-sm" @click="deleteContract(contract.id)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices Section -->
            <div v-if="activeTab === 'invoices'" class="content-section">
                <div class="section-header">
                    <h2>Invoices</h2>
                    <button class="btn btn-primary" @click="showInvoiceModal = true; editingInvoice = null">
                        Create Invoice
                    </button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Invoice Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id">
                                <td>{{ getCustomerName(invoice.customerId) }}</td>
                                <td>{{ invoice.invoiceDate }}</td>
                                <td>${{ calculateInvoiceTotal(invoice) }}</td>
                                <td>
                                    <span class="status-badge" :class="invoice.done ? 'status-done' : 'status-undone'">
                                        {{ invoice.done ? 'Done' : 'Undone' }}
                                    </span>
                                </td>
                                <td>{{ getUserName(invoice.createdBy) }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" @click="editInvoice(invoice)">Edit</button>
                                    <button class="btn btn-danger btn-sm" @click="deleteInvoice(invoice.id)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User Actions Logs Section -->
            <div v-if="activeTab === 'logs'" class="content-section">
                <div class="section-header">
                    <h2>User Actions Logs</h2>
                    <div class="filters">
                        <select v-model="logsFilter.actionType" @change="fetchLogs" class="form-control" style="width: 150px; display: inline-block; margin-right: 10px;">
                            <option value="">All Actions</option>
                            <option value="CREATE">Create</option>
                            <option value="UPDATE">Update</option>
                            <option value="DELETE">Delete</option>
                        </select>
                        <select v-model="logsFilter.tableName" @change="fetchLogs" class="form-control" style="width: 150px; display: inline-block; margin-right: 10px;">
                            <option value="">All Tables</option>
                            <option value="customers">Customers</option>
                            <option value="contracts">Contracts</option>
                            <option value="invoices">Invoices</option>
                        </select>
                        <button class="btn btn-secondary btn-sm" @click="clearLogsFilters">Clear Filters</button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Record ID</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs" :key="log.id">
                                <td>{{ formatDateTime(log.created_at) }}</td>
                                <td>{{ getUserName(log.user_id) }}</td>
                                <td>
                                    <span class="action-badge" :class="'action-' + (log.action_type || '').toLowerCase()">
                                        {{ log.action_type }}
                                    </span>
                                </td>
                                <td>{{ log.record_id }}</td>
                                <td>{{ log.action_description }}</td>
                                <td>
                                    <span class="status-badge" :class="log.status === 'success' ? 'status-success' : 'status-error'">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td>{{ log.ip_address }}</td>
                                <td>
                                    <button class="btn btn-info btn-sm" @click="viewLogDetails(log)">Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Log Details Modal -->
            <div v-if="showLogModal" class="modal" @click.self="showLogModal = false">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h3>Log Details</h3>
                        <button class="close-btn" @click="showLogModal = false">&times;</button>
                    </div>
                    
                    <div class="log-details">
                        <div class="detail-row">
                            <strong>Action:</strong> {{ selectedLog.action_type }} on {{ selectedLog.table_name }}
                        </div>
                        <div class="detail-row">
                            <strong>User:</strong> {{ getUserName(selectedLog.user_id) }} (ID: {{ selectedLog.user_id }})
                        </div>
                        <div class="detail-row">
                            <strong>Date/Time:</strong> {{ formatDateTime(selectedLog.created_at) }}
                        </div>
                        <div class="detail-row">
                            <strong>Record ID:</strong> {{ selectedLog.record_id }}
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong> {{ selectedLog.action_description }}
                        </div>
                        <div class="detail-row">
                            <strong>Status:</strong> {{ selectedLog.status }}
                        </div>
                        <div class="detail-row" v-if="selectedLog.error_message">
                            <strong>Error Message:</strong> {{ selectedLog.error_message }}
                        </div>
                        <div class="detail-row">
                            <strong>IP Address:</strong> {{ selectedLog.ip_address }}
                        </div>
                        <div class="detail-row">
                            <strong>User Agent:</strong> {{ selectedLog.user_agent }}
                        </div>
                        <div class="detail-row">
                            <strong>Session ID:</strong> {{ selectedLog.session_id }}
                        </div>
                        
                        <div v-if="selectedLog.old_values" class="values-section">
                            <h4>Old Values:</h4>
                            <pre>{{ formatJSON(selectedLog.old_values) }}</pre>
                        </div>
                        
                        <div v-if="selectedLog.new_values" class="values-section">
                            <h4>New Values:</h4>
                            <pre>{{ formatJSON(selectedLog.new_values) }}</pre>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button class="btn" @click="showLogModal = false">Close</button>
                    </div>
                </div>
            </div>
            <!-- Customer Modal -->
            <div v-if="showCustomerModal" class="modal" @click.self="showCustomerModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ editingCustomer ? 'Edit Customer' : 'Add Customer' }}</h3>
                        <button class="close-btn" @click="showCustomerModal = false">&times;</button>
                    </div>
                    
                    <form @submit.prevent="saveCustomer">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" class="form-control" v-model="customerForm.name" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" class="form-control" v-model="customerForm.phone" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address *</label>
                            <textarea class="form-control" v-model="customerForm.address" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Free Trial</label>
                                <input type="text" class="form-control" v-model="customerForm.freeTrial">
                            </div>
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" v-model="customerForm.startDate" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Note</label>
                            <textarea class="form-control" v-model="customerForm.note" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Created By User *</label>
                            <input type="text" class="form-control" v-model="customerForm.createdBy" required disabled>
                        </div>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn" @click="showCustomerModal = false" style="margin-right: 10px;">Cancel</button>
                            <button type="submit" class="btn btn-success">{{ editingCustomer ? 'Update' : 'Save' }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contract Modal -->
            <div v-if="showContractModal" class="modal" @click.self="showContractModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ editingContract ? 'Edit Contract' : 'Add Contract' }}</h3>
                        <button class="close-btn" @click="showContractModal = false">&times;</button>
                    </div>
                    
                    <form @submit.prevent="saveContract">
                        <div class="form-group">
                            <label>Customer *</label>
                            <select class="form-control" v-model="contractForm.customerId" required>
                                <option value="">Select a customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" v-model="contractForm.startDate" required>
                            </div>
                            <div class="form-group">
                                <label>Expire Date *</label>
                                <input type="date" class="form-control" v-model="contractForm.expireDate" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Amount *</label>
                            <input type="number" class="form-control" v-model="contractForm.payment" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Note</label>
                            <textarea class="form-control" v-model="contractForm.note" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Created By User *</label>
                            <input type="text" class="form-control" v-model="contractForm.createdBy" required disabled>
                        </div>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn" @click="showContractModal = false" style="margin-right: 10px;">Cancel</button>
                            <button type="submit" class="btn btn-success">{{ editingContract ? 'Update' : 'Save' }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Invoice Modal -->
            <div v-if="showInvoiceModal" class="modal" @click.self="showInvoiceModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ editingInvoice ? 'Edit Invoice' : 'Create Invoice' }}</h3>
                        <button class="close-btn" @click="showInvoiceModal = false">&times;</button>
                    </div>
                    
                    <form @submit.prevent="saveInvoice">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Customer *</label>
                                <select class="form-control" v-model="invoiceForm.customerId" required>
                                    <option value="">Select a customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Invoice Date *</label>
                                <input type="date" class="form-control" v-model="invoiceForm.invoiceDate" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" v-model="invoiceForm.done">
                                    <option :value="false">Undone</option>
                                    <option :value="true">Done</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Created By User *</label>
                                <input type="text" class="form-control" v-model="invoiceForm.createdBy" required disabled>
                            </div>
                        </div>
                        
                        <div class="invoice-items">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4>Invoice Items</h4>
                                <button type="button" class="btn btn-primary btn-sm" @click="addInvoiceItem">Add Item</button>
                            </div>
                            
                            <div v-for="(item, index) in invoiceForm.items" :key="index" class="item-row">
                                <input type="text" placeholder="Item name" v-model="item.name" class="form-control" required>
                                <input type="number" placeholder="Price" v-model="item.price" step="0.01" class="form-control" required @input="updateSubtotal(index)">
                                <input type="number" placeholder="Qty" v-model="item.quantity" class="form-control" required @input="updateSubtotal(index)">
                                <input type="number" placeholder="Subtotal" v-model="item.subtotal" class="form-control" readonly>
                                <input type="text" placeholder="Note" v-model="item.note" class="form-control">
                                <button type="button" class="btn btn-danger btn-sm" @click="removeInvoiceItem(index)">Remove</button>
                            </div>
                        </div>
                        
                        <div class="total-section">
                            <div class="total-amount">Total: ${{ calculateFormTotal() }}</div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn" @click="showInvoiceModal = false" style="margin-right: 10px;">Cancel</button>
                            <button type="submit" class="btn btn-success">{{ editingInvoice ? 'Update' : 'Save' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Pass PHP session data to JavaScript -->
    <script>
        // Make PHP session data available to JavaScript
        window.currentUserId = <?php echo json_encode($user_id); ?>;
        window.currentUsername = <?php echo json_encode($username); ?>;
    </script>

    <script src="dashboard_script.js"></script>
</body>
</html>