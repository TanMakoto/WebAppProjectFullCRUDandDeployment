/**
 * Northwind BMS — Application Controller & UI Logic
 * Responsive SPA handling all 12 screens & CRUD operations
 */

const App = {
  currentScreen: 'dashboard',
  user: null,

  init() {
    // Check Authentication
    this.user = API.getUser();
    if (!this.user || !API.getToken()) {
      this.showScreen('login');
    } else {
      this.updateUserUI();
      this.showScreen('dashboard');
    }

    // Bind event listeners
    this.bindEvents();
    this.loadGlobalSelects();
  },

  updateUserUI() {
    if (!this.user) return;
    document.querySelectorAll('.js-user-name').forEach(el => el.textContent = this.user.full_name || 'ผู้ดูแลระบบ');
    document.querySelectorAll('.js-user-role').forEach(el => el.textContent = this.user.role || 'ผู้ดูแลระบบ');
    document.querySelectorAll('.js-user-avatar').forEach(el => el.textContent = this.user.avatar || 'วช');
  },

  showScreen(screenId) {
    this.currentScreen = screenId;

    // Toggle screen views
    document.querySelectorAll('.screen').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(screenId);
    if (target) {
      target.classList.add('active');
    }

    // Toggle top navigation pills
    document.querySelectorAll('.nav-tab-pill').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.screen === screenId);
    });

    // Toggle sidebar navigation items
    document.querySelectorAll('.nav-item').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.screen === screenId);
    });

    // Update breadcrumb
    const breadcrumbTitles = {
      'login': 'เข้าสู่ระบบ',
      'dashboard': 'แดชบอร์ด',
      'products': 'รายการสินค้า',
      'product-form': 'เพิ่ม/แก้ไขสินค้า',
      'categories': 'หมวดหมู่สินค้า',
      'orders': 'คำสั่งซื้อ',
      'order-detail': 'รายละเอียดคำสั่งซื้อ',
      'customers': 'ลูกค้า',
      'suppliers': 'ผู้จัดจำหน่าย',
      'employees': 'พนักงาน',
      'shippers': 'บริษัทขนส่ง',
      'reports': 'รายงาน'
    };
    const title = breadcrumbTitles[screenId] || 'แดชบอร์ด';
    document.querySelectorAll('.js-cur-breadcrumb').forEach(el => el.textContent = title);

    window.scrollTo(0, 0);

    // Load data for screen
    this.loadScreenData(screenId);
  },

  loadScreenData(screenId) {
    switch (screenId) {
      case 'dashboard':
        this.loadDashboard();
        break;
      case 'products':
        this.loadProducts();
        break;
      case 'categories':
        this.loadCategories();
        break;
      case 'orders':
        this.loadOrders();
        break;
      case 'customers':
        this.loadCustomers();
        break;
      case 'suppliers':
        this.loadSuppliers();
        break;
      case 'employees':
        this.loadEmployees();
        break;
      case 'shippers':
        this.loadShippers();
        break;
      case 'reports':
        this.loadReports();
        break;
    }
  },

  // ----------------------------------------------------
  // Global & Select Option Helpers
  // ----------------------------------------------------
  async loadGlobalSelects() {
    try {
      const [catsRes, supsRes] = await Promise.all([
        API.getCategories(),
        API.getSuppliers()
      ]);

      if (catsRes.status && catsRes.data) {
        const catSelects = document.querySelectorAll('.js-category-select');
        catSelects.forEach(sel => {
          const firstOpt = sel.firstElementChild ? sel.firstElementChild.outerHTML : '<option value="">ทุกหมวดหมู่</option>';
          sel.innerHTML = firstOpt + catsRes.data.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
        });
      }

      if (supsRes.status && supsRes.data) {
        const supSelects = document.querySelectorAll('.js-supplier-select');
        supSelects.forEach(sel => {
          const firstOpt = sel.firstElementChild ? sel.firstElementChild.outerHTML : '<option value="">ทุกผู้จัดจำหน่าย</option>';
          sel.innerHTML = firstOpt + supsRes.data.map(s => `<option value="${s.supplier_id}">${s.company_name}</option>`).join('');
        });
      }
    } catch (e) {
      console.warn('Could not preload selects:', e);
    }
  },

  // ----------------------------------------------------
  // 1. Dashboard
  // ----------------------------------------------------
  async loadDashboard() {
    try {
      const res = await API.getDashboard();
      if (!res.status || !res.data) return;

      const { kpis, recent_orders, monthly_sales, top_products } = res.data;

      // KPIs
      document.getElementById('dash-revenue').textContent = `฿${Number(kpis.revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      document.getElementById('dash-orders').textContent = Number(kpis.orders || 0).toLocaleString();
      document.getElementById('dash-products').textContent = Number(kpis.products || 0).toLocaleString();
      document.getElementById('dash-customers').textContent = Number(kpis.customers || 0).toLocaleString();

      // Recent Orders Table
      const tb = document.getElementById('dash-recent-orders');
      if (tb && recent_orders) {
        tb.innerHTML = recent_orders.map(o => `
          <tr>
            <td><strong style="color:var(--primary);cursor:pointer;" onclick="App.viewOrder(${o.order_id})">#${o.order_id}</strong></td>
            <td><strong>${o.customer_name || 'ลูกค้าทั่วไป'}</strong></td>
            <td>${o.employee_name || '-'}</td>
            <td>${o.order_date}</td>
            <td>${o.shipper_name || '-'}</td>
            <td>${this.renderOrderStatusBadge(o.status)}</td>
          </tr>
        `).join('');
      }

      // Render Bar Chart
      this.renderMonthlyChart('dash-monthly-chart', monthly_sales);

      // Render Top Products
      const topProdWrap = document.getElementById('dash-top-products');
      if (topProdWrap && top_products) {
        topProdWrap.innerHTML = top_products.map(p => `
          <div class="prog-row">
            <div class="prog-label">
              <span>${p.product_name}</span>
              <span style="font-weight:700;font-family:'IBM Plex Mono'">${p.total_sold} หน่วย</span>
            </div>
            <div class="prog-bar">
              <div class="prog-fill" style="width:${p.percentage || 50}%"></div>
            </div>
          </div>
        `).join('');
      }
    } catch (err) {
      console.error(err);
    }
  },

  renderMonthlyChart(containerId, data) {
    const el = document.getElementById(containerId);
    if (!el || !data) return;

    const maxVal = Math.max(...data.map(d => d.total), 1);
    el.innerHTML = data.map(d => {
      const heightPercent = Math.max(12, Math.round((d.total / maxVal) * 100));
      return `
        <div class="bar-wrap">
          <div class="bar-col" style="height:${heightPercent}%;" data-tooltip="฿${Number(d.total).toLocaleString()}"></div>
          <div class="bar-lbl">${d.name}</div>
        </div>
      `;
    }).join('');
  },

  renderOrderStatusBadge(status) {
    if (status === 'จัดส่งแล้ว') {
      return '<span class="badge-nw badge-green">● จัดส่งแล้ว</span>';
    } else if (status === 'รอดำเนินการ') {
      return '<span class="badge-nw badge-yellow">● รอดำเนินการ</span>';
    } else if (status === 'กำลังเตรียมของ') {
      return '<span class="badge-nw badge-blue">● กำลังเตรียมของ</span>';
    } else {
      return `<span class="badge-nw badge-red">● ${status || 'ยกเลิก'}</span>`;
    }
  },

  // ----------------------------------------------------
  // 2. Products
  // ----------------------------------------------------
  productFilter: { search: '', category_id: '', status: '', page: 1, limit: 10 },

  async loadProducts() {
    try {
      const res = await API.getProducts(this.productFilter);
      const tb = document.getElementById('products-table-body');
      const countEl = document.getElementById('products-count-badge');
      if (countEl) countEl.textContent = `${res.total || 0} รายการ`;

      if (tb) {
        if (!res.data || res.data.length === 0) {
          tb.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลสินค้าตามเงื่อนไขที่ค้นหา</td></tr>';
          return;
        }

        tb.innerHTML = res.data.map(p => {
          let statusBadge = '<span class="badge-nw badge-green">พร้อมจำหน่าย</span>';
          if (p.discontinued == 1) {
            statusBadge = '<span class="badge-nw badge-red">เลิกจำหน่าย</span>';
          } else if (p.units_in_stock <= 0) {
            statusBadge = '<span class="badge-nw badge-red">สินค้าหมด</span>';
          } else if (p.units_in_stock <= 10) {
            statusBadge = '<span class="badge-nw badge-yellow">สต็อกต่ำ</span>';
          }

          return `
            <tr>
              <td class="font-monospace text-muted">#${p.product_id}</td>
              <td><strong>${p.product_name}</strong></td>
              <td>${p.category_name || '-'}</td>
              <td>${p.supplier_name || '-'}</td>
              <td><span class="text-muted">${p.quantity_per_unit || '-'}</span></td>
              <td><strong class="font-monospace">฿${Number(p.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong></td>
              <td>${statusBadge}</td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.editProduct(${p.product_id})" title="แก้ไข">✏️</button>
                  <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteProduct(${p.product_id})" title="ลบ">🗑️</button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      }

      this.renderPagination('products-pagination', res.total, this.productFilter.page, this.productFilter.limit, (newPage) => {
        this.productFilter.page = newPage;
        this.loadProducts();
      });
    } catch (err) {
      console.error(err);
    }
  },

  async editProduct(id) {
    try {
      const res = await API.getProduct(id);
      if (res.status && res.data) {
        const p = res.data;
        document.getElementById('pf-id').value = p.product_id;
        document.getElementById('pf-name').value = p.product_name;
        document.getElementById('pf-category').value = p.category_id || '';
        document.getElementById('pf-supplier').value = p.supplier_id || '';
        document.getElementById('pf-package').value = p.quantity_per_unit || '';
        document.getElementById('pf-price').value = p.unit_price;
        document.getElementById('pf-stock').value = p.units_in_stock;
        document.getElementById('pf-discontinued').value = p.discontinued;
        document.getElementById('pf-title').textContent = 'แก้ไขสินค้า: ' + p.product_name;
        this.showScreen('product-form');
      }
    } catch (e) {
      alert('ไม่สามารถดึงข้อมูลสินค้าได้');
    }
  },

  openAddProduct() {
    document.getElementById('pf-id').value = '';
    document.getElementById('pf-name').value = '';
    document.getElementById('pf-category').value = '';
    document.getElementById('pf-supplier').value = '';
    document.getElementById('pf-package').value = '';
    document.getElementById('pf-price').value = '';
    document.getElementById('pf-stock').value = '10';
    document.getElementById('pf-discontinued').value = '0';
    document.getElementById('pf-title').textContent = 'เพิ่มสินค้าใหม่';
    this.showScreen('product-form');
  },

  async saveProduct(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('pf-id').value;
    const data = {
      product_name: document.getElementById('pf-name').value.trim(),
      category_id: document.getElementById('pf-category').value,
      supplier_id: document.getElementById('pf-supplier').value,
      quantity_per_unit: document.getElementById('pf-package').value.trim(),
      unit_price: parseFloat(document.getElementById('pf-price').value) || 0,
      units_in_stock: parseInt(document.getElementById('pf-stock').value) || 0,
      discontinued: parseInt(document.getElementById('pf-discontinued').value) || 0
    };

    if (!data.product_name) {
      alert('กรุณากรอกชื่อสินค้า');
      return;
    }

    try {
      if (id) {
        await API.updateProduct(id, data);
        alert('แก้ไขสินค้าเรียบร้อยแล้ว!');
      } else {
        await API.createProduct(data);
        alert('เพิ่มสินค้าใหม่เรียบร้อยแล้ว!');
      }
      this.showScreen('products');
    } catch (err) {
      alert('เกิดข้อผิดพลาด: ' + err.message);
    }
  },

  async deleteProduct(id) {
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้ารหัส #' + id + ' ?')) return;
    try {
      await API.deleteProduct(id);
      alert('ลบสินค้าเรียบร้อยแล้ว');
      this.loadProducts();
    } catch (err) {
      alert('ไม่สามารถลบสินค้าได้: ' + err.message);
    }
  },

  // ----------------------------------------------------
  // 3. Categories (CRUD with Bootstrap Modal)
  // ----------------------------------------------------
  async loadCategories() {
    try {
      const res = await API.getCategories();
      const wrap = document.getElementById('categories-grid');
      const countEl = document.getElementById('categories-count');
      if (countEl) countEl.textContent = `${res.data ? res.data.length : 0} หมวดหมู่`;

      if (wrap && res.data) {
        wrap.innerHTML = res.data.map(c => `
          <div class="cat-card">
            <div>
              <div class="cat-icon">${c.icon || '📦'}</div>
              <div class="cat-name">${c.category_name}</div>
              <div class="cat-desc">${c.description || '-'}</div>
              <div class="cat-count">${c.product_count || 0} รายการสินค้า</div>
            </div>
            <div style="display:flex;gap:6px;margin-top:14px;">
              <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.editCategory(${c.category_id}, '${encodeURIComponent(c.category_name)}', '${encodeURIComponent(c.description || '')}', '${c.icon || '📦'}')">✏️ แก้ไข</button>
              <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteCategory(${c.category_id})">🗑️ ลบ</button>
            </div>
          </div>
        `).join('');
      }
    } catch (err) {
      console.error(err);
    }
  },

  openAddCategory() {
    document.getElementById('cat-modal-id').value = '';
    document.getElementById('cat-modal-name').value = '';
    document.getElementById('cat-modal-desc').value = '';
    document.getElementById('cat-modal-icon').value = '📦';
    document.getElementById('modalCategoryTitle').textContent = '➕ เพิ่มหมวดหมู่สินค้าใหม่';
    new bootstrap.Modal(document.getElementById('modalCategory')).show();
  },

  editCategory(id, encName, encDesc, icon) {
    document.getElementById('cat-modal-id').value = id;
    document.getElementById('cat-modal-name').value = decodeURIComponent(encName);
    document.getElementById('cat-modal-desc').value = decodeURIComponent(encDesc || '');
    document.getElementById('cat-modal-icon').value = icon || '📦';
    document.getElementById('modalCategoryTitle').textContent = '✏️ แก้ไขหมวดหมู่สินค้า';
    new bootstrap.Modal(document.getElementById('modalCategory')).show();
  },

  async submitCategoryModal(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('cat-modal-id').value;
    const name = document.getElementById('cat-modal-name').value.trim();
    const desc = document.getElementById('cat-modal-desc').value.trim();
    const icon = document.getElementById('cat-modal-icon').value.trim() || '📦';

    if (!name) return;
    try {
      if (id) {
        await API.updateCategory(id, { category_name: name, description: desc, icon });
      } else {
        await API.createCategory({ category_name: name, description: desc, icon });
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalCategory'));
      if (modal) modal.hide();
      this.loadCategories();
      this.loadGlobalSelects();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteCategory(id) {
    if (!confirm('ยืนยันลบหมวดหมู่นี้?')) return;
    try {
      await API.deleteCategory(id);
      this.loadCategories();
      this.loadGlobalSelects();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 4. Orders & Order Details
  // ----------------------------------------------------
  orderFilter: { search: '', status: '', page: 1, limit: 10 },

  async loadOrders() {
    try {
      const res = await API.getOrders(this.orderFilter);
      const tb = document.getElementById('orders-table-body');
      const countEl = document.getElementById('orders-count-badge');
      if (countEl) countEl.textContent = `${res.total || 0} รายการ`;

      if (tb) {
        if (!res.data || res.data.length === 0) {
          tb.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">ไม่พบคำสั่งซื้อ</td></tr>';
          return;
        }

        tb.innerHTML = res.data.map(o => `
          <tr>
            <td><strong style="color:var(--primary);cursor:pointer;" onclick="App.viewOrder(${o.order_id})">#${o.order_id}</strong></td>
            <td><strong>${o.customer_name || 'ลูกค้าทั่วไป'}</strong></td>
            <td>${o.employee_name || '-'}</td>
            <td>${o.order_date}</td>
            <td>${o.shipper_name || '-'}</td>
            <td><span class="badge-nw badge-blue">${o.item_count || 1} รายการ</span></td>
            <td>${this.renderOrderStatusBadge(o.status)}</td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.viewOrder(${o.order_id})">🧾 ดูรายละเอียด</button>
                <button class="btn-nw btn-nw-sm ${o.status === 'จัดส่งแล้ว' ? 'btn-nw-outline' : 'btn-nw-success'}" onclick="App.changeOrderStatus(${o.order_id}, '${o.status}')">🚚 อัปเดต</button>
              </div>
            </td>
          </tr>
        `).join('');
      }

      this.renderPagination('orders-pagination', res.total, this.orderFilter.page, this.orderFilter.limit, (p) => {
        this.orderFilter.page = p;
        this.loadOrders();
      });
    } catch (e) {
      console.error(e);
    }
  },

  async viewOrder(orderId) {
    try {
      const res = await API.getOrder(orderId);
      if (!res.status || !res.data) return;
      const o = res.data;

      document.getElementById('od-id').textContent = `#${o.order_id}`;
      document.getElementById('od-date').textContent = o.order_date;
      document.getElementById('od-status').innerHTML = this.renderOrderStatusBadge(o.status);

      document.getElementById('od-customer-name').textContent = o.customer_name || '-';
      document.getElementById('od-contact').textContent = o.contact_name || '-';
      document.getElementById('od-address').textContent = `${o.ship_address || ''} ${o.ship_city || ''} ${o.ship_country || ''}`;
      document.getElementById('od-shipper').textContent = `${o.shipper_name || '-'} (${o.shipper_phone || '-'})`;

      const tb = document.getElementById('od-items-tbody');
      if (tb && o.items) {
        tb.innerHTML = o.items.map(it => `
          <tr>
            <td>
              <strong>${it.product_name}</strong>
              <div class="text-muted" style="font-size:12px;">${it.quantity_per_unit || ''}</div>
            </td>
            <td>${it.quantity}</td>
            <td>฿${Number(it.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td><strong>฿${Number(it.subtotal).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong></td>
          </tr>
        `).join('');
      }

      document.getElementById('od-subtotal').textContent = `฿${Number(o.subtotal || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
      document.getElementById('od-freight').textContent = `฿${Number(o.freight || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
      document.getElementById('od-total').textContent = `฿${Number(o.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;

      this.showScreen('order-detail');
    } catch (e) {
      alert('ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
    }
  },

  async changeOrderStatus(id, currentStatus) {
    const nextStatus = currentStatus === 'จัดส่งแล้ว' ? 'รอดำเนินการ' : 'จัดส่งแล้ว';
    if (!confirm(`ต้องการเปลี่ยนสถานะคำสั่งซื้อ #${id} เป็น "${nextStatus}" หรือไม่?`)) return;
    try {
      await API.updateOrderStatus(id, nextStatus);
      alert('อัปเดตสถานะสำเร็จ!');
      this.loadOrders();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 5. Customers
  // ----------------------------------------------------
  async loadCustomers(search = '') {
    try {
      const res = await API.getCustomers(search);
      const tb = document.getElementById('customers-table-body');
      const countEl = document.getElementById('customers-count');
      if (countEl) countEl.textContent = `${res.total || (res.data ? res.data.length : 0)} ราย`;

      if (tb && res.data) {
        tb.innerHTML = res.data.map(c => `
          <tr>
            <td class="font-monospace fw-bold text-primary">${c.customer_id}</td>
            <td><strong>${c.company_name}</strong></td>
            <td>${c.contact_name || '-'}</td>
            <td>${c.city || '-'}</td>
            <td>${c.country || '-'}</td>
            <td class="font-monospace text-muted">${c.postal_code || '-'}</td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.editCustomer('${c.customer_id}', '${encodeURIComponent(c.company_name)}', '${encodeURIComponent(c.contact_name || '')}', '${encodeURIComponent(c.city || '')}', '${encodeURIComponent(c.country || '')}', '${encodeURIComponent(c.phone || '')}')">✏️</button>
                <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteCustomer('${c.customer_id}')">🗑️</button>
              </div>
            </td>
          </tr>
        `).join('');
      }
    } catch (e) {
      console.error(e);
    }
  },

  openAddCustomer() {
    document.getElementById('cust-modal-id').value = '';
    document.getElementById('cust-modal-id').readOnly = false;
    document.getElementById('cust-modal-company').value = '';
    document.getElementById('cust-modal-contact').value = '';
    document.getElementById('cust-modal-city').value = '';
    document.getElementById('cust-modal-country').value = 'Thailand';
    document.getElementById('cust-modal-phone').value = '';
    document.getElementById('modalCustomerTitle').textContent = '➕ เพิ่มลูกค้าใหม่';
    new bootstrap.Modal(document.getElementById('modalCustomer')).show();
  },

  editCustomer(id, encCompany, encContact, encCity, encCountry, encPhone) {
    document.getElementById('cust-modal-id').value = id;
    document.getElementById('cust-modal-id').readOnly = true;
    document.getElementById('cust-modal-company').value = decodeURIComponent(encCompany);
    document.getElementById('cust-modal-contact').value = decodeURIComponent(encContact || '');
    document.getElementById('cust-modal-city').value = decodeURIComponent(encCity || '');
    document.getElementById('cust-modal-country').value = decodeURIComponent(encCountry || 'Thailand');
    document.getElementById('cust-modal-phone').value = decodeURIComponent(encPhone || '');
    document.getElementById('modalCustomerTitle').textContent = `✏️ แก้ไขข้อมูลลูกค้า: ${id}`;
    new bootstrap.Modal(document.getElementById('modalCustomer')).show();
  },

  async submitCustomerModal(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('cust-modal-id').value.trim().toUpperCase();
    const company = document.getElementById('cust-modal-company').value.trim();
    const contact = document.getElementById('cust-modal-contact').value.trim();
    const city = document.getElementById('cust-modal-city').value.trim();
    const country = document.getElementById('cust-modal-country').value.trim();
    const phone = document.getElementById('cust-modal-phone').value.trim();

    if (!id || !company) return;
    const isEdit = document.getElementById('cust-modal-id').readOnly;

    try {
      if (isEdit) {
        await API.updateCustomer(id, { company_name: company, contact_name: contact, city, country, phone });
      } else {
        await API.createCustomer({ customer_id: id, company_name: company, contact_name: contact, city, country, phone });
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalCustomer'));
      if (modal) modal.hide();
      this.loadCustomers();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteCustomer(id) {
    if (!confirm('ยืนยันลบลูกค้ารหัส ' + id + ' ?')) return;
    try {
      await API.deleteCustomer(id);
      this.loadCustomers();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 6. Suppliers
  // ----------------------------------------------------
  async loadSuppliers(search = '') {
    try {
      const res = await API.getSuppliers(search);
      const tb = document.getElementById('suppliers-table-body');
      const countEl = document.getElementById('suppliers-count');
      if (countEl) countEl.textContent = `${res.data ? res.data.length : 0} ราย`;

      if (tb && res.data) {
        tb.innerHTML = res.data.map(s => `
          <tr>
            <td class="font-monospace text-muted">#${s.supplier_id}</td>
            <td><strong>${s.company_name}</strong></td>
            <td>${s.contact_name || '-'}</td>
            <td>${s.city || '-'}</td>
            <td>${s.country || '-'}</td>
            <td class="font-monospace">${s.phone || '-'}</td>
            <td><span class="badge-nw badge-blue">${s.product_count || 0} รายการ</span></td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.editSupplier(${s.supplier_id}, '${encodeURIComponent(s.company_name)}')">✏️</button>
                <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteSupplier(${s.supplier_id})">🗑️</button>
              </div>
            </td>
          </tr>
        `).join('');
      }
    } catch (e) {
      console.error(e);
    }
  },

  openAddSupplier() {
    document.getElementById('sup-modal-id').value = '';
    document.getElementById('sup-modal-company').value = '';
    document.getElementById('sup-modal-contact').value = '';
    document.getElementById('sup-modal-city').value = '';
    document.getElementById('sup-modal-country').value = 'Thailand';
    document.getElementById('sup-modal-phone').value = '';
    document.getElementById('modalSupplierTitle').textContent = '➕ เพิ่มผู้จัดจำหน่ายใหม่';
    new bootstrap.Modal(document.getElementById('modalSupplier')).show();
  },

  editSupplier(id, encName) {
    document.getElementById('sup-modal-id').value = id;
    document.getElementById('sup-modal-company').value = decodeURIComponent(encName);
    document.getElementById('sup-modal-contact').value = '';
    document.getElementById('sup-modal-city').value = '';
    document.getElementById('sup-modal-country').value = 'Thailand';
    document.getElementById('sup-modal-phone').value = '';
    document.getElementById('modalSupplierTitle').textContent = '✏️ แก้ไขผู้จัดจำหน่าย';
    new bootstrap.Modal(document.getElementById('modalSupplier')).show();
  },

  async submitSupplierModal(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('sup-modal-id').value;
    const company = document.getElementById('sup-modal-company').value.trim();
    const contact = document.getElementById('sup-modal-contact').value.trim();
    const city = document.getElementById('sup-modal-city').value.trim();
    const country = document.getElementById('sup-modal-country').value.trim();
    const phone = document.getElementById('sup-modal-phone').value.trim();

    if (!company) return;
    try {
      if (id) {
        await API.updateSupplier(id, { company_name: company, contact_name: contact, city, country, phone });
      } else {
        await API.createSupplier({ company_name: company, contact_name: contact, city, country, phone });
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalSupplier'));
      if (modal) modal.hide();
      this.loadSuppliers();
      this.loadGlobalSelects();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteSupplier(id) {
    if (!confirm('ยืนยันลบผู้จัดจำหน่ายนี้?')) return;
    try {
      await API.deleteSupplier(id);
      this.loadSuppliers();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 7. Employees
  // ----------------------------------------------------
  async loadEmployees() {
    try {
      const res = await API.getEmployees();
      const wrap = document.getElementById('employees-grid');
      const countEl = document.getElementById('employees-count');
      if (countEl) countEl.textContent = `${res.data ? res.data.length : 0} คน`;

      if (wrap && res.data) {
        wrap.innerHTML = res.data.map(e => `
          <div class="emp-card">
            <div class="emp-avatar">${e.avatar || (e.first_name[0] + e.last_name[0])}</div>
            <div class="emp-name">${e.first_name} ${e.last_name}</div>
            <div class="emp-title">${e.title || 'พนักงาน'}</div>
            <div class="emp-meta">เกิด: ${e.birth_date || '-'}</div>
            <div style="margin-top:14px;display:flex;justify-content:center;gap:6px;">
              <button class="btn-nw btn-nw-outline btn-nw-sm" onclick="App.editEmployee(${e.employee_id}, '${e.first_name}', '${e.last_name}', '${e.title}')">✏️ แก้ไข</button>
              <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteEmployee(${e.employee_id})">🗑️</button>
            </div>
          </div>
        `).join('');
      }
    } catch (e) {
      console.error(e);
    }
  },

  openAddEmployee() {
    document.getElementById('emp-modal-id').value = '';
    document.getElementById('emp-modal-firstname').value = '';
    document.getElementById('emp-modal-lastname').value = '';
    document.getElementById('emp-modal-title').value = '';
    document.getElementById('emp-modal-birthdate').value = '';
    document.getElementById('modalEmployeeTitle').textContent = '➕ เพิ่มพนักงานใหม่';
    new bootstrap.Modal(document.getElementById('modalEmployee')).show();
  },

  editEmployee(id, first, last, title) {
    document.getElementById('emp-modal-id').value = id;
    document.getElementById('emp-modal-firstname').value = first;
    document.getElementById('emp-modal-lastname').value = last;
    document.getElementById('emp-modal-title').value = title;
    document.getElementById('emp-modal-birthdate').value = '';
    document.getElementById('modalEmployeeTitle').textContent = `✏️ แก้ไขข้อมูลพนักงาน: ${first} ${last}`;
    new bootstrap.Modal(document.getElementById('modalEmployee')).show();
  },

  async submitEmployeeModal(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('emp-modal-id').value;
    const first = document.getElementById('emp-modal-firstname').value.trim();
    const last = document.getElementById('emp-modal-lastname').value.trim();
    const title = document.getElementById('emp-modal-title').value.trim();
    const birthDate = document.getElementById('emp-modal-birthdate').value;

    if (!first || !last || !title) return;
    try {
      if (id) {
        await API.updateEmployee(id, { first_name: first, last_name: last, title, birth_date: birthDate });
      } else {
        await API.createEmployee({ first_name: first, last_name: last, title, birth_date: birthDate });
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalEmployee'));
      if (modal) modal.hide();
      this.loadEmployees();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteEmployee(id) {
    if (!confirm('ยืนยันลบพนักงานนี้?')) return;
    try {
      await API.deleteEmployee(id);
      this.loadEmployees();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 8. Shippers
  // ----------------------------------------------------
  async loadShippers() {
    try {
      const res = await API.getShippers();
      const wrap = document.getElementById('shippers-grid');
      const countEl = document.getElementById('shippers-count');
      if (countEl) countEl.textContent = `${res.data ? res.data.length : 0} บริษัท`;

      if (wrap && res.data) {
        wrap.innerHTML = res.data.map(s => `
          <div class="shipper-card">
            <div style="font-size:32px;margin-bottom:12px;">${s.icon || '🚚'}</div>
            <div style="font-size:16px;font-weight:700;color:var(--ink);">${s.company_name}</div>
            <div style="font-size:13px;color:var(--ink-2);margin-top:4px;">${s.phone || '-'}</div>
            <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:12px;color:var(--ink-3);">จัดส่งแล้ว ${s.shipment_count || 0} ครั้ง</span>
              <span class="badge-nw badge-green">พร้อมให้บริการ</span>
            </div>
            <div style="margin-top:14px;display:flex;gap:6px;">
              <button class="btn-nw btn-nw-outline btn-nw-sm" style="flex:1" onclick="App.editShipper(${s.shipper_id}, '${encodeURIComponent(s.company_name)}')">✏️ แก้ไข</button>
              <button class="btn-nw btn-nw-danger btn-nw-sm" onclick="App.deleteShipper(${s.shipper_id})">🗑️</button>
            </div>
          </div>
        `).join('');
      }
    } catch (e) {
      console.error(e);
    }
  },

  openAddShipper() {
    document.getElementById('ship-modal-id').value = '';
    document.getElementById('ship-modal-company').value = '';
    document.getElementById('ship-modal-phone').value = '';
    document.getElementById('ship-modal-icon').value = '🚚';
    document.getElementById('modalShipperTitle').textContent = '➕ เพิ่มบริษัทขนส่งใหม่';
    new bootstrap.Modal(document.getElementById('modalShipper')).show();
  },

  editShipper(id, encName) {
    document.getElementById('ship-modal-id').value = id;
    document.getElementById('ship-modal-company').value = decodeURIComponent(encName);
    document.getElementById('ship-modal-phone').value = '';
    document.getElementById('ship-modal-icon').value = '🚚';
    document.getElementById('modalShipperTitle').textContent = '✏️ แก้ไขบริษัทขนส่ง';
    new bootstrap.Modal(document.getElementById('modalShipper')).show();
  },

  async submitShipperModal(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('ship-modal-id').value;
    const company = document.getElementById('ship-modal-company').value.trim();
    const phone = document.getElementById('ship-modal-phone').value.trim();
    const icon = document.getElementById('ship-modal-icon').value.trim() || '🚚';

    if (!company) return;
    try {
      if (id) {
        await API.updateShipper(id, { company_name: company, phone, icon });
      } else {
        await API.createShipper({ company_name: company, phone, icon });
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalShipper'));
      if (modal) modal.hide();
      this.loadShippers();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteShipper(id) {
    if (!confirm('ยืนยันลบบริษัทขนส่งนี้?')) return;
    try {
      await API.deleteShipper(id);
      this.loadShippers();
    } catch (e) {
      alert(e.message);
    }
  },

  // ----------------------------------------------------
  // 9. Reports & Bonus Export
  // ----------------------------------------------------
  async loadReports() {
    try {
      const res = await API.getReports();
      if (!res.status || !res.data) return;

      const { monthly_sales, top_products, top_customers } = res.data;

      // Render Reports Bar Chart
      this.renderMonthlyChart('reports-monthly-chart', monthly_sales);

      // Render Top Products
      const pWrap = document.getElementById('reports-top-products');
      if (pWrap && top_products) {
        pWrap.innerHTML = top_products.map(p => `
          <div class="prog-row">
            <div class="prog-label">
              <span>${p.product_name}</span>
              <span style="font-weight:700;font-family:'IBM Plex Mono'">${p.total_sold} หน่วย (฿${Number(p.total_sales).toLocaleString()})</span>
            </div>
            <div class="prog-bar">
              <div class="prog-fill" style="width:${p.percentage || 50}%"></div>
            </div>
          </div>
        `).join('');
      }

      // Render Top Customers Table
      const cTb = document.getElementById('reports-top-customers');
      if (cTb && top_customers) {
        const medalIcons = ['🥇', '🥈', '🥉', '4', '5'];
        cTb.innerHTML = top_customers.map((c, i) => `
          <tr>
            <td style="font-weight:700;font-size:16px;">${medalIcons[i] || (i + 1)}</td>
            <td><strong>${c.company_name}</strong></td>
            <td>${c.country}</td>
            <td><strong class="font-monospace text-primary">${c.order_count}</strong></td>
            <td>
              <div class="prog-bar" style="width:120px;">
                <div class="prog-fill" style="width:${c.percentage || 40}%"></div>
              </div>
            </td>
          </tr>
        `).join('');
      }
    } catch (e) {
      console.error(e);
    }
  },

  async exportReportCSV(type = 'products') {
    try {
      const res = await API.exportData(type);
      if (!res.status || !res.data || res.data.length === 0) {
        alert('ไม่มีข้อมูลสำหรับส่งออก');
        return;
      }

      const items = res.data;
      const headers = Object.keys(items[0]);
      let csvContent = '\uFEFF' + headers.join(',') + '\n';

      items.forEach(row => {
        const line = headers.map(h => {
          let val = row[h] === null || row[h] === undefined ? '' : String(row[h]);
          return `"${val.replace(/"/g, '""')}"`;
        }).join(',');
        csvContent += line + '\n';
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `northwind_${type}_report_${new Date().toISOString().slice(0,10)}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (e) {
      alert('ส่งออกรายงานล้มเหลว: ' + e.message);
    }
  },

  printReportPDF() {
    window.print();
  },

  // ----------------------------------------------------
  // Helpers: Pagination & Events
  // ----------------------------------------------------
  renderPagination(containerId, total, currentPage, limit, onPageClick) {
    const el = document.getElementById(containerId);
    if (!el) return;

    const totalPages = Math.ceil(total / Math.max(1, limit)) || 1;
    if (totalPages <= 1) {
      el.innerHTML = `<span class="text-muted">ทั้งหมด ${total} รายการ</span>`;
      return;
    }

    let buttons = '';
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
        buttons += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="(${onPageClick.toString()})(${i})">${i}</button>`;
      }
    }

    el.innerHTML = `
      <div class="text-muted">หน้า ${currentPage} จาก ${totalPages} (ทั้งหมด ${total} รายการ)</div>
      <div class="page-btn-group">
        <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="(${onPageClick.toString()})(${currentPage - 1})">‹ ก่อนหน้า</button>
        ${buttons}
        <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="(${onPageClick.toString()})(${currentPage + 1})">ถัดไป ›</button>
      </div>
    `;
  },

  bindEvents() {
    // Login form submit
    const loginBtn = document.getElementById('login-btn');
    if (loginBtn) {
      loginBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const u = document.getElementById('login-username').value.trim();
        const p = document.getElementById('login-password').value.trim();
        if (!u || !p) {
          alert('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
          return;
        }
        try {
          loginBtn.disabled = true;
          loginBtn.textContent = 'กำลังตรวจสอบ...';
          await API.login(u, p);
          this.user = API.getUser();
          this.updateUserUI();
          this.showScreen('dashboard');
        } catch (err) {
          alert(err.message || 'เข้าสู่ระบบไม่สำเร็จ');
        } finally {
          loginBtn.disabled = false;
          loginBtn.textContent = '🔐 เข้าสู่ระบบ';
        }
      });
    }

    // Product search & filter inputs
    const prodSearch = document.getElementById('products-search-input');
    if (prodSearch) {
      prodSearch.addEventListener('input', (e) => {
        this.productFilter.search = e.target.value.trim();
        this.productFilter.page = 1;
        this.loadProducts();
      });
    }

    const prodCatFilter = document.getElementById('products-cat-filter');
    if (prodCatFilter) {
      prodCatFilter.addEventListener('change', (e) => {
        this.productFilter.category_id = e.target.value;
        this.productFilter.page = 1;
        this.loadProducts();
      });
    }

    const prodStatusFilter = document.getElementById('products-status-filter');
    if (prodStatusFilter) {
      prodStatusFilter.addEventListener('change', (e) => {
        this.productFilter.status = e.target.value;
        this.productFilter.page = 1;
        this.loadProducts();
      });
    }

    // Orders search & status filter
    const orderSearch = document.getElementById('orders-search-input');
    if (orderSearch) {
      orderSearch.addEventListener('input', (e) => {
        this.orderFilter.search = e.target.value.trim();
        this.orderFilter.page = 1;
        this.loadOrders();
      });
    }

    const orderStatusFilter = document.getElementById('orders-status-filter');
    if (orderStatusFilter) {
      orderStatusFilter.addEventListener('change', (e) => {
        this.orderFilter.status = e.target.value;
        this.orderFilter.page = 1;
        this.loadOrders();
      });
    }

    // Customers search
    const custSearch = document.getElementById('customers-search-input');
    if (custSearch) {
      custSearch.addEventListener('input', (e) => {
        this.loadCustomers(e.target.value.trim());
      });
    }

    // Suppliers search
    const supSearch = document.getElementById('suppliers-search-input');
    if (supSearch) {
      supSearch.addEventListener('input', (e) => {
        this.loadSuppliers(e.target.value.trim());
      });
    }
  },

  toggleTheme() {
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('nw_theme', next);
  }
};

// Global shorthand for inline onclick handlers matching the prototype
function show(screenId) {
  App.showScreen(screenId);
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('nw_theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
  App.init();
});

