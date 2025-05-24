const { createApp } = Vue;
createApp({
    data() {
        return {
            activeTab: 'invoices',

            showLogModal: false,

            // Data arrays
            customers: [],
            contracts: [],
            invoices: [],
            userNames: {},
            logs: [],
            selectedLog: {},
            
            

            logsFilter: {
                actionType: '',
                tableName: ''
            },

            // Loading states
            loading: {
                customers: false,
                contracts: false,
                invoices: false,
                userNames: false,
                logs: false
                
            },

            // Error handling
            errors: {
                customers: null,
                contracts: null,
                invoices: null,
                userNames: null,
                logs: null
                
            },

            // Modal states
            showCustomerModal: false,
            showContractModal: false,
            showInvoiceModal: false,

            // Editing states
            editingCustomer: null,
            editingContract: null,
            editingInvoice: null,

            // Form data
            customerForm: this.getEmptyCustomerForm(),
            contractForm: this.getEmptyContractForm(),
            invoiceForm: this.getEmptyInvoiceForm(),

            // ID counters
            nextCustomerId: 3,
            nextContractId: 2,
            nextInvoiceId: 2,

            // API base URL - adjust this to server path
            apiBaseUrl: 'http://localhost/ca/dashboard/API'
        }
    },

    methods: {
        // Data fetching methods
        async fetchCustomers() {
            this.loading.customers = true;
            this.errors.customers = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/get_customers.php`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.customers = result.data;
                    // Update next ID counter
                    if (this.customers.length > 0) {
                        this.nextCustomerId = Math.max(...this.customers.map(c => c.id)) + 1;
                    }
                } else {
                    throw new Error(result.message || 'Failed to fetch customers');
                }

            } catch (error) {
                console.error('Error fetching customers:', error);
                this.errors.customers = error.message;
                alert('Failed to load customers: ' + error.message);
            } finally {
                this.loading.customers = false;
            }
        },

        async fetchContracts() {
            this.loading.contracts = true;
            this.errors.contracts = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/get_contracts.php`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.contracts = result.data;
                    // Update next ID counter
                    if (this.contracts.length > 0) {
                        this.nextContractId = Math.max(...this.contracts.map(c => c.id)) + 1;
                    }
                } else {
                    throw new Error(result.message || 'Failed to fetch contracts');
                }

            } catch (error) {
                console.error('Error fetching contracts:', error);
                this.errors.contracts = error.message;
                alert('Failed to load contracts: ' + error.message);
            } finally {
                this.loading.contracts = false;
            }
        },

        async fetchInvoices() {
            this.loading.invoices = true;
            this.errors.invoices = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/get_invoices.php`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.invoices = result.data;
                    // Update next ID counter
                    if (this.invoices.length > 0) {
                        this.nextInvoiceId = Math.max(...this.invoices.map(i => i.id)) + 1;
                    }
                } else {
                    throw new Error(result.message || 'Failed to fetch invoices');
                }

            } catch (error) {
                console.error('Error fetching invoices:', error);
                this.errors.invoices = error.message;
                alert('Failed to load invoices: ' + error.message);
            } finally {
                this.loading.invoices = false;
            }
        },

        async fetchUserNames() {
            this.loading.userNames = true;
            this.errors.userNames = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/get_user_name.php`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.userNames = result.data;
                } else {
                    throw new Error(result.message || 'Failed to fetch user names');
                }

            } catch (error) {
                console.error('Error fetching user names:', error);
                this.errors.userNames = error.message;
                // Don't show alert for user names as it's not critical
            } finally {
                this.loading.userNames = false;
            }
        },

        // Logs methods
        async fetchLogs() {
            this.loading.logs = true;
            this.errors.logs = null;

            try {
                let url = `${this.apiBaseUrl}/get_logs.php`;
                const params = new URLSearchParams();
                
                if (this.logsFilter.actionType) {
                    params.append('action_type', this.logsFilter.actionType);
                }
                if (this.logsFilter.tableName) {
                    params.append('table_name', this.logsFilter.tableName);
                }
                
                if (params.toString()) {
                    url += '?' + params.toString();
                }

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.logs = result.data;
                } else {
                    throw new Error(result.message || 'Failed to fetch logs');
                }

            } catch (error) {
                console.error('Error fetching logs:', error);
                this.errors.logs = error.message;
                alert('Failed to load logs: ' + error.message);
            } finally {
                this.loading.logs = false;
            }
        },

        viewLogDetails(log) {
            this.selectedLog = log;
            this.showLogModal = true;
        },

        clearLogsFilters() {
            this.logsFilter = {
                actionType: '',
                tableName: ''
            };
            this.fetchLogs();
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString();
        },

        formatJSON(jsonStr) {
            if (!jsonStr) return '';
            try {
                return JSON.stringify(JSON.parse(jsonStr), null, 2);
            } catch (e) {
                return jsonStr;
            }
        },
        // Customer methods
        getEmptyCustomerForm() {
            return {
                name: '',
                address: '',
                phone: '',
                freeTrial: '',
                startDate: '',
                note: '',
                createdBy: window.currentUsername
            };
        },

        validateCustomerForm() {
            const errors = [];

            if (!this.customerForm.name || this.customerForm.name.trim() === '') {
                errors.push('Customer name is required');
            }

            // Validate phone number format if provided
            if (this.customerForm.phone && this.customerForm.phone.trim() !== '' &&
                !/^[\d\-\+\(\)\s]+$/.test(this.customerForm.phone)) {
                errors.push('Invalid phone number format. Please use only numbers, spaces, hyphens, plus signs, and parentheses.');
            }

            // Validate start date format if provided
            if (this.customerForm.startDate && this.customerForm.startDate.trim() !== '') {
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateRegex.test(this.customerForm.startDate)) {
                    errors.push('Invalid start date format. Please use YYYY-MM-DD format.');
                } else {
                    // Additional validation: check if it's a valid date
                    const testDate = new Date(this.customerForm.startDate);
                    if (isNaN(testDate.getTime())) {
                        errors.push('Invalid start date. Please enter a valid date in YYYY-MM-DD format.');
                    }
                }
            }

            return errors;
        },

        editCustomer(customer) {
            try {
                console.log('Editing customer:', customer);

                this.editingCustomer = { ...customer }; // Create a copy to avoid reference issues

                // Populate form with customer data - handle both camelCase and snake_case
                this.customerForm = {
                    name: customer.name || '',
                    address: customer.address || '',
                    phone: customer.phone || customer.phone_number || '',
                    freeTrial: customer.freeTrial || customer.free_trial || '',
                    startDate: customer.startDate || customer.start_date || '',
                    note: customer.note || '',
                    createdBy: customer.createdBy
                };

                console.log('Customer form populated:', this.customerForm);
                this.showCustomerModal = true;

            } catch (error) {
                console.error('Error editing customer:', error);
                alert('Error loading customer data: ' + error.message);
            }
        },

        async saveCustomer() {
            try {
                // Validate form data
                const errors = this.validateCustomerForm();

                if (errors.length > 0) {
                    alert('Please fix the following errors:\n' + errors.join('\n'));
                    return;
                }

                // Prepare data for API (matching the PHP expected format)
                const customerData = {
                    name: this.customerForm.name.trim(),
                    address: this.customerForm.address?.trim() || '',
                    phone: this.customerForm.phone?.trim() || '',
                    freeTrial: this.customerForm.freeTrial?.trim() || '',
                    startDate: this.customerForm.startDate?.trim() || '',
                    note: this.customerForm.note?.trim() || '',
                    createdBy: this.customer_id
                };

                console.log('Customer data being sent:', customerData);

                let response;
                let result;

                if (this.editingCustomer) {
                    // Update existing customer
                    console.log('Updating customer ID:', this.editingCustomer.id);

                    response = await fetch(`${this.apiBaseUrl}/update_customer.php?id=${this.editingCustomer.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(customerData)
                    });

                } else {
                    // Create new customer
                    console.log('Creating new customer');

                    response = await fetch(`${this.apiBaseUrl}/insert_customer.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(customerData)
                    });
                }

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                // Get response text first to handle potential non-JSON responses
                const responseText = await response.text();
                console.log('Raw response:', responseText);

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Parse JSON response
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response. Server returned: ${responseText.substring(0, 200)}${responseText.length > 200 ? '...' : ''}`);
                }

                // Handle API response
                if (result.success) {
                    const action = this.editingCustomer ? 'updated' : 'created';
                    alert(`Customer ${action} successfully!`);

                    // Refresh customers list to show updated data
                    await this.fetchCustomers();

                    // Close modal and reset form
                    this.closeCustomerModal();

                } else {
                    throw new Error(result.message || `Failed to ${this.editingCustomer ? 'update' : 'create'} customer`);
                }

            }
            catch (error) {
                console.error('Error saving customer:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    customerData: this.customerForm
                });

                // Show user-friendly error message
                const action = this.editingCustomer ? 'update' : 'create';
                alert(`Failed to ${action} customer: ${error.message}`);
            }
        },


        closeCustomerModal() {
            this.showCustomerModal = false;
            this.customerForm = this.getEmptyCustomerForm();
            this.editingCustomer = null;
        },

        async deleteCustomer(id) {
            if (!confirm('Are you sure you want to delete this customer? This will also delete all associated contracts and invoices. This action cannot be undone.')) {
                return;
            }

            try {
                console.log('Deleting customer with ID:', id);

                const response = await fetch(`${this.apiBaseUrl}/delete_customer.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                console.log('Delete response status:', response.status);

                // Get the response text first to check what we're actually receiving
                const responseText = await response.text();
                console.log('Delete response:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}...`);
                }

                if (result.success) {
                    alert('Customer deleted successfully!');

                    // Refresh all data since we deleted a customer
                    await this.fetchCustomers();
                    await this.fetchContracts(); // Refresh contracts as they might be affected
                    await this.fetchInvoices(); // Refresh invoices as they might be affected
                } else {
                    throw new Error(result.message || 'Failed to delete customer');
                }

            } catch (error) {
                console.error('Error deleting customer:', error);
                console.error('Error stack:', error.stack);
                alert('Failed to delete customer: ' + error.message);
            }
        },

        // Contract methods
        getEmptyContractForm() {
            return {
                customerId: '',
                startDate: '',
                expireDate: '',
                payment: '',
                note: '',
                createdBy: window.currentUsername
            };
        },

        validateContractForm() {
            const errors = [];

            if (!this.contractForm.customerId) {
                errors.push('Please select a customer');
            }

            if (!this.contractForm.startDate) {
                errors.push('Please select a start date');
            }

            if (!this.contractForm.payment || this.contractForm.payment <= 0) {
                errors.push('Please enter a valid payment amount');
            }

            // Validate dates
            if (this.contractForm.expireDate && this.contractForm.startDate) {
                const startDate = new Date(this.contractForm.startDate);
                const expireDate = new Date(this.contractForm.expireDate);

                if (expireDate <= startDate) {
                    errors.push('Expire date must be after start date');
                }
            }

            // Check if customer exists in the customers array
            if (this.contractForm.customerId) {
                const customerExists = this.customers.some(c => c.id == this.contractForm.customerId);
                if (!customerExists) {
                    errors.push('Selected customer does not exist');
                }
            }

            return errors;
        },

        editContract(contract) {
            try {
                console.log('Editing contract:', contract);

                this.editingContract = { ...contract }; // Create a copy to avoid reference issues

                // Populate form with contract data - handle both camelCase and snake_case
                this.contractForm = {
                    customerId: String(contract.customerId || contract.customer_id || ''),
                    startDate: contract.startDate || contract.start_date || '',
                    expireDate: contract.expireDate || contract.expire_date || '',
                    payment: parseFloat(contract.payment) || 0,
                    note: contract.note || '',
                    createdBy: contract.createdBy
                };

                console.log('Contract form populated:', this.contractForm);
                this.showContractModal = true;

            } catch (error) {
                console.error('Error editing contract:', error);
                alert('Error loading contract data: ' + error.message);
            }
        },

        async saveContract() {
            try {
                // Validate form data
                const errors = this.validateContractForm();

                if (errors.length > 0) {
                    alert('Please fix the following errors:\n' + errors.join('\n'));
                    return;
                }

                // Set expire date to startDate if not provided (based on your PHP validation)
                if (!this.contractForm.expireDate) {
                    this.contractForm.expireDate = this.contractForm.startDate;
                }

                // Prepare data for API
                const contractData = {
                    customerId: parseInt(this.contractForm.customerId),
                    startDate: this.contractForm.startDate,
                    expireDate: this.contractForm.expireDate,
                    payment: parseFloat(this.contractForm.payment),
                    note: this.contractForm.note || '',
                    createdBy: window.currentUserId
                };

                console.log('Contract data being sent:', contractData);

                let response;
                let result;

                if (this.editingContract) {
                    // Update existing contract
                    console.log('Updating contract ID:', this.editingContract.id);

                    response = await fetch(`${this.apiBaseUrl}/update_contract.php?id=${this.editingContract.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(contractData)
                    });

                } else {
                    // Create new contract
                    console.log('Creating new contract');

                    response = await fetch(`${this.apiBaseUrl}/insert_contract.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(contractData)
                    });
                }

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                // Get response text first to handle potential non-JSON responses
                const responseText = await response.text();
                console.log('Raw response:', responseText);

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Parse JSON response
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response. Server returned: ${responseText.substring(0, 200)}${responseText.length > 200 ? '...' : ''}`);
                }

                // Handle API response
                if (result.success) {
                    const action = this.editingContract ? 'updated' : 'created';
                    alert(`Contract ${action} successfully!`);

                    // Refresh contracts list to show updated data
                    await this.fetchContracts();

                    // Close modal and reset form
                    this.closeContractModal();

                } else {
                    throw new Error(result.message || `Failed to ${this.editingContract ? 'update' : 'create'} contract`);
                }

            } catch (error) {
                console.error('Error saving contract:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    contractData: this.contractForm
                });

                // Show user-friendly error message
                const action = this.editingContract ? 'update' : 'create';
                alert(`Failed to ${action} contract: ${error.message}`);
            }
        },

        closeContractModal() {
            this.showContractModal = false;
            this.contractForm = this.getEmptyContractForm();
            this.editingContract = null;
        },

        async deleteContract(id) {
            if (!confirm('Are you sure you want to delete this contract? This action cannot be undone.')) {
                return;
            }

            try {
                console.log('Deleting contract with ID:', id);

                const response = await fetch(`${this.apiBaseUrl}/delete_contract.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                console.log('Delete response status:', response.status);

                // Get the response text first to check what we're actually receiving
                const responseText = await response.text();
                console.log('Delete response:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}...`);
                }

                if (result.success) {
                    alert('Contract deleted successfully!');
                    // Refresh the contracts list
                    await this.fetchContracts();
                } else {
                    throw new Error(result.message || 'Failed to delete contract');
                }

            } catch (error) {
                console.error('Error deleting contract:', error);
                console.error('Error stack:', error.stack);
                alert('Failed to delete contract: ' + error.message);
            }
        },

        // Invoice methods
        getEmptyInvoiceForm() {
            return {
                customerId: '',
                invoiceDate: '',
                done: false,
                createdBy: window.currentUsername,
                items: []
            };
        },

        editInvoice(invoice) {
            this.editingInvoice = invoice;
            this.invoiceForm = {
                customerId: invoice.customerId,
                invoiceDate: invoice.invoiceDate,
                done: invoice.done,
                createdBy: invoice.createdBy,
                items: invoice.items ? invoice.items.map(item => ({
                    name: item.name || item.item_name, // Handle both naming conventions
                    price: parseFloat(item.price) || 0,
                    quantity: parseInt(item.quantity) || 0,
                    subtotal: parseFloat(item.subtotal) || 0,
                    note: item.note || ''
                })) : []
            };
            this.showInvoiceModal = true;
        },

        async saveInvoice() {
            try {
                // Validate form data
                if (!this.invoiceForm.customerId) {
                    alert('Please select a customer');
                    return;
                }

                if (!this.invoiceForm.invoiceDate) {
                    alert('Please select an invoice date');
                    return;
                }

                if (!this.invoiceForm.items || this.invoiceForm.items.length === 0) {
                    alert('Please add at least one item to the invoice');
                    return;
                }

                // Validate each item
                for (let i = 0; i < this.invoiceForm.items.length; i++) {
                    const item = this.invoiceForm.items[i];
                    if (!item.name || !item.price || !item.quantity) {
                        alert(`Please fill in all required fields for item #${i + 1}`);
                        return;
                    }
                }

                // Prepare data for API
                const invoiceData = {
                    customerId: parseInt(this.invoiceForm.customerId),
                    invoiceDate: this.invoiceForm.invoiceDate,
                    done: this.invoiceForm.done,
                    createdBy: window.currentUserId,
                    items: this.invoiceForm.items.map(item => ({
                        name: item.name,
                        price: parseFloat(item.price),
                        quantity: parseInt(item.quantity),
                        subtotal: parseFloat(item.subtotal),
                        note: item.note || ''
                    }))
                };

                let response;
                let result;

                if (this.editingInvoice) {
                    // Update existing invoice via API
                    console.log('Updating invoice:', this.editingInvoice.id, invoiceData);

                    response = await fetch(`${this.apiBaseUrl}/update_invoice.php?id=${this.editingInvoice.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(invoiceData)
                    });

                } else {
                    // Create new invoice via API
                    console.log('Creating new invoice:', invoiceData);

                    response = await fetch(`${this.apiBaseUrl}/insert_invoice.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(invoiceData)
                    });
                }

                console.log('Response status:', response.status);

                // Get the response text first to check what we're actually receiving
                const responseText = await response.text();
                console.log('Raw response:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Try to parse JSON
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}...`);
                }

                if (result.success) {
                    const action = this.editingInvoice ? 'updated' : 'created';
                    alert(`Invoice ${action} successfully!`);
                } else {
                    throw new Error(result.message || `Failed to ${this.editingInvoice ? 'update' : 'create'} invoice`);
                }

                // Refresh the invoices list
                await this.fetchInvoices();

                // Close modal and reset form
                this.showInvoiceModal = false;
                this.invoiceForm = this.getEmptyInvoiceForm();
                this.editingInvoice = null;

            } catch (error) {
                console.error('Error saving invoice:', error);
                console.error('Error stack:', error.stack);
                alert('Failed to save invoice: ' + error.message);
            }
        },


        async deleteInvoice(id) {
            if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
                return;
            }

            try {
                console.log('Deleting invoice with ID:', id);

                const response = await fetch(`${this.apiBaseUrl}/delete_invoice.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                console.log('Delete response status:', response.status);

                // Get the response text first to check what we're actually receiving
                const responseText = await response.text();
                console.log('Delete response:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}...`);
                }

                if (result.success) {
                    alert('Invoice deleted successfully!');
                    // Refresh the invoices list
                    await this.fetchInvoices();
                } else {
                    throw new Error(result.message || 'Failed to delete invoice');
                }

            } catch (error) {
                console.error('Error deleting invoice:', error);
                console.error('Error stack:', error.stack);
                alert('Failed to delete invoice: ' + error.message);
            }
        },

        addInvoiceItem() {
            this.invoiceForm.items.push({
                name: '',
                price: 0,
                quantity: 1,
                subtotal: 0,
                note: ''
            });
        },

        removeInvoiceItem(index) {
            this.invoiceForm.items.splice(index, 1);
        },

        updateSubtotal(index) {
            const item = this.invoiceForm.items[index];
            item.subtotal = (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
        },

        calculateFormTotal() {
            return this.invoiceForm.items.reduce((total, item) => {
                return total + (parseFloat(item.subtotal) || 0);
            }, 0).toFixed(2);
        },

        calculateInvoiceTotal(invoice) {
            return invoice.items.reduce((total, item) => {
                return total + (parseFloat(item.subtotal) || 0);
            }, 0).toFixed(2);
        },

        // Utility methods
        getCustomerName(customerId) {
            const customer = this.customers.find(c => c.id === customerId);
            return customer ? customer.name : 'Unknown Customer';
        },

        // get user name
        getUserName(userId) {
            return this.userNames[userId] || `User ID: ${userId}`;
        },
    },

    watch: {
        showCustomerModal(newVal) {
            if (!newVal) {
                this.customerForm = this.getEmptyCustomerForm();
                this.editingCustomer = null;
            }
        },

        showContractModal(newVal) {
            if (!newVal) {
                this.contractForm = this.getEmptyContractForm();
                this.editingContract = null;
            }
        },

        showInvoiceModal(newVal) {
            if (!newVal) {
                this.invoiceForm = this.getEmptyInvoiceForm();
                this.editingInvoice = null;
            }
        }
    },

    async mounted() {
        // Set default date to today for new forms
        const today = new Date().toISOString().split('T')[0];
        this.customerForm.startDate = today;
        this.contractForm.startDate = today;
        this.invoiceForm.invoiceDate = today;

        // Fetch data when component mounts
        await this.fetchCustomers();
        await this.fetchContracts();
        await this.fetchInvoices();
        await this.fetchUserNames();
        await this.fetchLogs();
    }
}).mount('#app');