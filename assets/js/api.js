/**
 * Northwind BMS — Frontend API Client
 * RESTful fetch wrapper with JWT Authorization
 */

const API = {
  // Determine correct base URL automatically
  getBaseUrl() {
    const loc = window.location;
    // If running under localhost with file or web server
    const path = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
    return `${loc.origin}${path}api/index.php?endpoint=`;
  },

  getToken() {
    return localStorage.getItem('nw_jwt_token');
  },

  setToken(token) {
    if (token) {
      localStorage.setItem('nw_jwt_token', token);
    } else {
      localStorage.removeItem('nw_jwt_token');
    }
  },

  getUser() {
    const u = localStorage.getItem('nw_user');
    return u ? JSON.parse(u) : null;
  },

  setUser(user) {
    if (user) {
      localStorage.setItem('nw_user', JSON.stringify(user));
    } else {
      localStorage.removeItem('nw_user');
    }
  },

  async request(endpoint, options = {}) {
    const url = `${this.getBaseUrl()}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };

    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      const data = await response.json().catch(() => ({
        status: false,
        message: 'Invalid JSON response from server'
      }));

      if (!response.ok) {
        if (response.status === 401 && !endpoint.includes('auth/login')) {
          this.setToken(null);
          this.setUser(null);
          App.showScreen('login');
        }
        throw new Error(data.message || `HTTP Error ${response.status}`);
      }

      return data;
    } catch (err) {
      console.error(`API Error [${endpoint}]:`, err);
      throw err;
    }
  },

  // Auth
  async login(username, password) {
    const res = await this.request('auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });
    if (res.status && res.data) {
      this.setToken(res.data.token);
      this.setUser(res.data.user);
    }
    return res;
  },

  logout() {
    this.setToken(null);
    this.setUser(null);
    App.showScreen('login');
  },

  async getMe() {
    return await this.request('auth/me');
  },

  // Dashboard
  async getDashboard() {
    return await this.request('dashboard');
  },

  // Products
  async getProducts(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await this.request(`products&${query}`);
  },

  async getProduct(id) {
    return await this.request(`products/${id}`);
  },

  async createProduct(data) {
    return await this.request('products', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateProduct(id, data) {
    return await this.request(`products/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteProduct(id) {
    return await this.request(`products/${id}`, {
      method: 'DELETE'
    });
  },

  // Categories
  async getCategories() {
    return await this.request('categories');
  },

  async createCategory(data) {
    return await this.request('categories', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateCategory(id, data) {
    return await this.request(`categories/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteCategory(id) {
    return await this.request(`categories/${id}`, {
      method: 'DELETE'
    });
  },

  // Orders
  async getOrders(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await this.request(`orders&${query}`);
  },

  async getOrder(id) {
    return await this.request(`orders/${id}`);
  },

  async updateOrderStatus(id, status) {
    return await this.request(`orders/${id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status })
    });
  },

  async deleteOrder(id) {
    return await this.request(`orders/${id}`, {
      method: 'DELETE'
    });
  },

  // Customers
  async getCustomers(search = '') {
    return await this.request(`customers&search=${encodeURIComponent(search)}`);
  },

  async createCustomer(data) {
    return await this.request('customers', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateCustomer(id, data) {
    return await this.request(`customers/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteCustomer(id) {
    return await this.request(`customers/${id}`, {
      method: 'DELETE'
    });
  },

  // Suppliers
  async getSuppliers(search = '') {
    return await this.request(`suppliers&search=${encodeURIComponent(search)}`);
  },

  async createSupplier(data) {
    return await this.request('suppliers', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateSupplier(id, data) {
    return await this.request(`suppliers/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteSupplier(id) {
    return await this.request(`suppliers/${id}`, {
      method: 'DELETE'
    });
  },

  // Employees
  async getEmployees() {
    return await this.request('employees');
  },

  async createEmployee(data) {
    return await this.request('employees', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateEmployee(id, data) {
    return await this.request(`employees/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteEmployee(id) {
    return await this.request(`employees/${id}`, {
      method: 'DELETE'
    });
  },

  // Shippers
  async getShippers() {
    return await this.request('shippers');
  },

  async createShipper(data) {
    return await this.request('shippers', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  async updateShipper(id, data) {
    return await this.request(`shippers/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  async deleteShipper(id) {
    return await this.request(`shippers/${id}`, {
      method: 'DELETE'
    });
  },

  // Reports
  async getReports() {
    return await this.request('reports/dashboard');
  },

  async exportData(type = 'products') {
    return await this.request(`reports/export&type=${type}`);
  }
};

