# 📘 Northwind BMS — API Documentation
**วิชา:** การพัฒนา Web Application (ภาคการศึกษา 2/2569)  
**สาขาวิศวกรรมคอมพิวเตอร์ (CPE PSRU)**  
**สถาปัตยกรรม:** Pure PHP RESTful API Architecture + PDO OOP + Prepared Statements + JWT Authentication  

---

## 1. ข้อมูลทั่วไป (Overview)
- **Base URL:** `http://localhost:8000/api` หรือ `https://your-domain.com/northwind/api`
- **Fallback URL:** `/api/index.php?endpoint={resource}`
- **Authentication:** JSON Web Token (JWT) ส่งผ่าน Header:
  ```http
  Authorization: Bearer <token>
  ```
- **Response Format:** JSON เสมอ (Content-Type: `application/json; charset=UTF-8`)
  - โครงสร้าง Success:
    ```json
    {
      "status": true,
      "message": "ข้อความสำเร็จ",
      "data": { ... }
    }
    ```
  - โครงสร้าง Error:
    ```json
    {
      "status": false,
      "message": "ข้อความแจ้งเตือนข้อผิดพลาด"
    }
    ```

---

## 2. รายการ Endpoints ทั้งหมด

### 🔐 ระบบยืนยันตัวตน (Authentication)

| Method | Endpoint | คำอธิบาย | ต้องการ JWT |
|---|---|---|---|
| `POST` | `/api/auth/login` | ตรวจสอบสิทธิ์ผู้ใช้และออก JWT Token | ❌ |
| `GET` | `/api/auth/me` | ตรวจสอบข้อมูลผู้ใช้ปัจจุบันจาก Token | ✅ |

#### ตัวอย่าง: `POST /api/auth/login`
- **Request Body:**
  ```json
  {
    "username": "admin@northwind.co.th",
    "password": "admin"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": true,
    "message": "เข้าสู่ระบบสำเร็จ",
    "data": {
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "user": {
        "id": 1,
        "username": "admin",
        "email": "admin@northwind.co.th",
        "full_name": "วชิรา ล.",
        "role": "ผู้ดูแลระบบ",
        "avatar": "วช"
      }
    }
  }
  ```

---

### 📊 แดชบอร์ด (Dashboard)

| Method | Endpoint | คำอธิบาย |
|---|---|---|
| `GET` | `/api/dashboard` | ดึงข้อมูลสรุปภาพรวม KPI, ยอดขายรายเดือน, ออเดอร์ล่าสุด |

- **Response (200 OK):**
  ```json
  {
    "status": true,
    "data": {
      "kpis": {
        "revenue": 424968.25,
        "orders": 12,
        "products": 18,
        "customers": 13
      },
      "recent_orders": [ ... ],
      "monthly_sales": [ ... ],
      "top_products": [ ... ]
    }
  }
  ```

---

### 📦 จัดการสินค้า (Products CRUD)

| Method | Endpoint | Query / Body | คำอธิบาย |
|---|---|---|---|
| `GET` | `/api/products` | `?search=&category_id=&status=&page=1&limit=20` | ดึงรายการสินค้าทั้งหมด |
| `GET` | `/api/products/{id}` | - | ดึงรายละเอียดสินค้าตาม ID |
| `POST` | `/api/products` | JSON Body | เพิ่มสินค้าใหม่ |
| `PUT` | `/api/products/{id}` | JSON Body | แก้ไขข้อมูลสินค้า |
| `DELETE` | `/api/products/{id}` | - | ลบสินค้าตาม ID |

#### ตัวอย่าง: `POST /api/products`
- **Request Body:**
  ```json
  {
    "product_name": "ชาเขียวออร์แกนิก มัทฉะ",
    "category_id": 1,
    "supplier_id": 1,
    "quantity_per_unit": "กล่องละ 50 ซอง",
    "unit_price": 450.00,
    "units_in_stock": 25,
    "discontinued": 0
  }
  ```

---

### 🛒 คำสั่งซื้อ (Orders CRUD & Status)

| Method | Endpoint | คำอธิบาย |
|---|---|---|
| `GET` | `/api/orders` | ดึงรายการออเดอร์ทั้งหมด (รองรับกรอง status, customer_id) |
| `GET` | `/api/orders/{id}` | ดูรายละเอียดออเดอร์ + รายการสินค้าในออเดอร์ |
| `POST` | `/api/orders` | บันทึกการสั่งซื้อใหม่พร้อมรายการสินค้า |
| `PUT` | `/api/orders/{id}` | เปลี่ยนสถานะออเดอร์ (เช่น จัดส่งแล้ว / รอดำเนินการ) |
| `DELETE` | `/api/orders/{id}` | ลบคำสั่งซื้อ |

---

### 🗂 หมวดหมู่สินค้า (Categories CRUD)
- `GET /api/categories` : ดึงรายการหมวดหมู่ พร้อมจำนวนสินค้าในหมวดหมู่นั้น
- `POST /api/categories` : เพิ่มหมวดหมู่ใหม่
- `PUT /api/categories/{id}` : แก้ไขหมวดหมู่
- `DELETE /api/categories/{id}` : ลบหมวดหมู่

---

### 👥 ลูกค้า (Customers CRUD)
- `GET /api/customers?search=` : ดึงรายชื่อลูกค้าและจำนวนออเดอร์
- `POST /api/customers` : เพิ่มลูกค้าใหม่
- `PUT /api/customers/{id}` : แก้ไขข้อมูลลูกค้า
- `DELETE /api/customers/{id}` : ลบลูกค้า

---

### 🏭 ผู้จัดจำหน่าย (Suppliers CRUD)
- `GET /api/suppliers` : ดึงรายชื่อผู้จัดจำหน่าย
- `POST /api/suppliers` : เพิ่มผู้จัดจำหน่าย
- `PUT /api/suppliers/{id}` : แก้ไขข้อมูล
- `DELETE /api/suppliers/{id}` : ลบข้อมูล

---

### 👤 พนักงาน (Employees CRUD)
- `GET /api/employees` : ดึงรายชื่อพนักงาน
- `POST /api/employees` : เพิ่มพนักงาน
- `PUT /api/employees/{id}` : แก้ไขข้อมูลพนักงาน
- `DELETE /api/employees/{id}` : ลบพนักงาน

---

### 🚚 บริษัทขนส่ง (Shippers CRUD)
- `GET /api/shippers` : ดึงรายชื่อบริษัทขนส่งและจำนวนครั้งที่จัดส่ง
- `POST /api/shippers` : เพิ่มบริษัทขนส่ง
- `PUT /api/shippers/{id}` : แก้ไขข้อมูลบริษัทขนส่ง
- `DELETE /api/shippers/{id}` : ลบข้อมูลบริษัทขนส่ง

---

### 📈 รายงานและการส่งออก (Reports & Export)
- `GET /api/reports/dashboard` : ยอดขายรายเดือน, Top 5 สินค้าขายดี, Top 5 ลูกค้า
- `GET /api/reports/export?type=products` : ส่งออกข้อมูลสำหรับสร้างรายงาน/Export (CSV/PDF)

