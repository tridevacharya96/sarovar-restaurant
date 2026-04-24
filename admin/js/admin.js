// admin/js/admin.js
'use strict';

const API = 'api/admin_api.php';
let currentPage = 'dashboard';
let orderFilter = 'all';
let resFilter   = 'all';
let menuFilter  = 'all';

/* ============================================================ INIT */
document.addEventListener('DOMContentLoaded', () => {
    loadPage('dashboard');
    loadBadges();
    setInterval(loadBadges, 60000);

    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            loadPage(item.dataset.page);
        });
    });

    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    document.getElementById('globalSearch').addEventListener('input', debounce(e => {
        const q = e.target.value.trim();
        if (q.length > 1 && currentPage === 'orders') renderOrdersPage(q);
    }, 400));
});

/* ============================================================ NAVIGATION */
function loadPage(page) {
    currentPage = page;
    document.querySelectorAll('.nav-item').forEach(i => {
        i.classList.toggle('active', i.dataset.page === page);
    });
    const titles = {dashboard:'Dashboard',events:'Event bookings',orders:'Orders',reservations:'Reservations',menu:'Menu items',customers:'Customers',reports:'Reports',coupons:'Coupons',messages:'Messages',settings:'Site Settings'};
    document.getElementById('pageTitle').textContent = titles[page] || page;
    document.getElementById('pageSub').textContent   = '';
    document.getElementById('content').innerHTML = '<div class="page-loader"><i class="fas fa-spinner fa-spin"></i></div>';
    const fn = {dashboard:renderDashboard,orders:renderOrdersPage,reservations:renderReservationsPage,menu:renderMenuPage,customers:renderCustomersPage,reports:renderReportsPage,messages:renderMessagesPage,events:renderEventsPage,coupons:renderCouponsPage,settings:renderSettingsPage};
    if (fn[page]) fn[page]();
}

function refreshCurrent() {
    const icon = document.getElementById('refreshIcon');
    icon.style.animation = 'spin 0.6s linear';
    setTimeout(() => icon.style.animation = '', 700);
    loadPage(currentPage);
}

/* ============================================================ BADGES */
async function loadBadges() {
    const data = await apiFetch('badges');
    if (!data) return;
    const pb = document.getElementById('pendingBadge');
    pb.textContent = data.pending_orders;
    pb.style.display = data.pending_orders > 0 ? '' : 'none';
    const rb = document.getElementById('pendingResBadge');
    rb.textContent = data.pending_reservations;
    rb.style.display = data.pending_reservations > 0 ? '' : 'none';
    const mb = document.getElementById('msgBadge');
    mb.textContent = data.new_messages;
    mb.style.display = data.new_messages > 0 ? '' : 'none';
    const mi = document.getElementById('msgIconBadge');
    mi.style.display = data.new_messages > 0 ? '' : 'none';
    const eb = document.getElementById('pendingEvtBadge');
   if (eb) { eb.textContent = data.pending_events; eb.style.display = data.pending_events > 0 ? '' : 'none'; }
}

/* ============================================================ DASHBOARD */
async function renderDashboard() {
    const data = await apiFetch('dashboard');
    if (!data) return;
    const s = data.stats;
    set('content', `
    <div class="metrics">
      <div class="metric"><div class="metric-accent" style="background:#D85A30"></div>
        <div class="metric-label">Today's revenue</div>
        <div class="metric-value">₹${fmtNum(s.today_revenue)}</div>
        <div class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> ₹${fmtNum(s.yesterday_revenue)} yesterday</div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#378ADD"></div>
        <div class="metric-label">Today's orders</div>
        <div class="metric-value">${s.today_orders}</div>
        <div class="metric-trend ${s.pending_orders>0?'trend-down':'trend-up'}">
          <i class="fas fa-clock"></i> ${s.pending_orders} pending
        </div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#1D9E75"></div>
        <div class="metric-label">Total customers</div>
        <div class="metric-value">${s.total_customers}</div>
        <div class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> +${s.new_customers_week} this week</div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#7F77DD"></div>
        <div class="metric-label">Monthly revenue</div>
        <div class="metric-value">₹${fmtNum(s.month_revenue)}</div>
        <div class="metric-trend trend-up"><i class="fas fa-chart-line"></i> ${s.month_orders} orders</div>
      </div>
    </div>
    <div class="grid2">
      <div class="card">
        <div class="card-header"><div class="card-title">Top selling items</div></div>
        <div id="topBars"></div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title">Revenue by category</div></div>
        <div class="donut-wrap">
          <svg width="110" height="110" viewBox="0 0 110 110" id="donutSvg"></svg>
          <div class="legend" id="donutLegend"></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">Recent orders</div>
        <button class="card-action" onclick="loadPage('orders')">View all →</button>
      </div>
      <div id="recentOrdersList"></div>
    </div>`);

    renderTopBars(data.top_items);
    renderDonut(data.category_revenue);
    renderRecentOrders(data.recent_orders);
}

function renderTopBars(items) {
    if (!items || !items.length) { set('topBars','<div class="empty-state"><p>No data yet</p></div>'); return; }
    const max = Math.max(...items.map(i=>parseFloat(i.revenue)));
    set('topBars', items.map(i=>`
    <div class="bar-row">
      <div class="bar-label">${esc(i.name)}</div>
      <div class="bar-track"><div class="bar-fill" style="width:0%;background:#D85A30" data-pct="${(i.revenue/max*100).toFixed(0)}"></div></div>
      <div class="bar-val">₹${fmtNum(i.revenue)}</div>
    </div>`).join(''));
    setTimeout(()=>document.querySelectorAll('#topBars .bar-fill').forEach(b=>b.style.width=b.dataset.pct+'%'),50);
}

function renderDonut(cats) {
    if (!cats || !cats.length) return;
    const colors = ['#D85A30','#1D9E75','#378ADD','#EF9F27','#7F77DD','#D4537E'];
    const total  = cats.reduce((s,c)=>s+parseFloat(c.revenue),0);
    const r=38, cx=55, cy=55, circ=2*Math.PI*r;
    let offset=0, svgPaths='', legend='';
    cats.forEach((c,i)=>{
        const pct = parseFloat(c.revenue)/total;
        const dash = (pct*circ).toFixed(2);
        svgPaths += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${colors[i%colors.length]}" stroke-width="20" stroke-dasharray="${dash} ${(circ-dash).toFixed(2)}" stroke-dashoffset="${(-offset).toFixed(2)}" transform="rotate(-90 ${cx} ${cy})"/>`;
        legend += `<div class="legend-row"><div class="legend-dot" style="background:${colors[i%colors.length]}"></div>${esc(c.name)} — ${(pct*100).toFixed(0)}%</div>`;
        offset += pct*circ;
    });
    document.getElementById('donutSvg').innerHTML = svgPaths;
    document.getElementById('donutLegend').innerHTML = legend;
}

function renderRecentOrders(orders) {
    if (!orders || !orders.length) { set('recentOrdersList','<div class="empty-state"><p>No orders yet</p></div>'); return; }
    set('recentOrdersList',`<table class="admin-table">
    <thead><tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody>${orders.map(o=>`<tr>
      <td class="bold">#${o.id}</td>
      <td>${esc(o.name)}</td>
      <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(o.items_summary||'—')}</td>
      <td class="bold">₹${fmtNum(o.total_amount)}</td>
      <td>${o.payment_method.toUpperCase()}</td>
      <td>${pill(o.status)}</td>
    </tr>`).join('')}</tbody></table>`);
}

/* ============================================================ ORDERS */
async function renderOrdersPage(search='') {
    const data = await apiFetch('orders', {filter:orderFilter, search});
    if (!data) return;
    set('content',`
    <div class="filter-row">
      ${['all','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'].map(f=>`
      <div class="filter-chip ${orderFilter===f?'active':''}" onclick="setOrderFilter('${f}',this)">${fmtStatus(f)}</div>`).join('')}
      <div class="filter-spacer"></div>
      <div class="search-inline"><i class="fas fa-search"></i><input id="orderSearch" placeholder="Search name, phone..." value="${esc(search)}"/></div>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Customer</th><th>Phone</th><th>Items</th><th>Total</th><th>Pay</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>${data.map(o=>`<tr>
          <td class="bold">#${o.id}</td>
          <td>${esc(o.name)}</td>
          <td>${esc(o.phone)}</td>
          <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(o.items_summary||'')}">${esc(o.items_summary||'—')}</td>
          <td class="bold">₹${fmtNum(o.total_amount)}</td>
          <td>${o.payment_method.toUpperCase()}</td>
          <td>${fmtDate(o.created_at)}</td>
          <td>${pill(o.status)}</td>
          <td><button class="btn btn-ghost btn-sm" onclick="openUpdateOrder(${o.id},'${o.status}')"><i class="fas fa-edit"></i> Update</button></td>
        </tr>`).join('')}
        ${!data.length?'<tr><td colspan="9" style="text-align:center;padding:32px;color:rgba(255,255,255,0.2)">No orders found</td></tr>':''}</tbody>
      </table>
    </div>`);

    document.getElementById('orderSearch').addEventListener('input', debounce(e=>{
        renderOrdersPage(e.target.value.trim());
    },400));
}

function setOrderFilter(f, el) {
    orderFilter = f;
    document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    renderOrdersPage();
}

function openUpdateOrder(id, currentStatus) {
    const statuses = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
    document.getElementById('modalBox').innerHTML = `
    <h3><i class="fas fa-edit"></i> Update Order #${id}</h3>
    <div class="form-group" style="margin-bottom:18px">
      <label>New Status</label>
      <select id="newStatus">
        ${statuses.map(s=>`<option value="${s}" ${s===currentStatus?'selected':''}>${fmtStatus(s)}</option>`).join('')}
      </select>
    </div>
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="updateOrder(${id})"><i class="fas fa-check"></i> Save</button>
    </div>`;
    openModal();
}

async function updateOrder(id) {
    const status = document.getElementById('newStatus').value;
    const res = await apiPost('update_order', {order_id:id, status});
    if (res && res.success) {
        toast('Order status updated', 'success');
        closeModal();
        loadBadges();
        renderOrdersPage();
    } else {
        toast(res?.error || 'Failed to update', 'error');
    }
}

/* ============================================================ RESERVATIONS */
async function renderReservationsPage() {
    const data = await apiFetch('reservations', {filter:resFilter});
    if (!data) return;
    set('content',`
    <div class="filter-row">
      ${['all','pending','confirmed','cancelled','completed'].map(f=>`
      <div class="filter-chip ${resFilter===f?'active':''}" onclick="setResFilter('${f}',this)">${fmtStatus(f)}</div>`).join('')}
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Guest</th><th>Phone</th><th>Email</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>${data.map(r=>`<tr>
          <td class="bold">#${r.id}</td>
          <td>${esc(r.name)}</td>
          <td>${esc(r.phone)}</td>
          <td style="font-size:11px">${esc(r.email)}</td>
          <td>${fmtDateOnly(r.date)}</td>
          <td>${fmtTime(r.time)}</td>
          <td>${r.guests}</td>
          <td>${pill(r.status)}</td>
          <td style="display:flex;gap:5px">
            ${r.status==='pending'?`<button class="btn btn-success btn-sm" onclick="updateRes(${r.id},'confirmed')"><i class="fas fa-check"></i></button>`:''}
            ${r.status!=='cancelled'&&r.status!=='completed'?`<button class="btn btn-danger btn-sm" onclick="updateRes(${r.id},'cancelled')"><i class="fas fa-times"></i></button>`:''}
            ${r.status==='confirmed'?`<button class="btn btn-ghost btn-sm" onclick="updateRes(${r.id},'completed')">Done</button>`:''}
          </td>
        </tr>`).join('')}
        ${!data.length?'<tr><td colspan="9" style="text-align:center;padding:32px;color:rgba(255,255,255,0.2)">No reservations found</td></tr>':''}</tbody>
      </table>
    </div>`);
}

function setResFilter(f, el) {
    resFilter = f;
    document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    renderReservationsPage();
}

async function updateRes(id, status) {
    const res = await apiPost('update_reservation', {reservation_id:id, status});
    if (res && res.success) {
        toast('Reservation updated', 'success');
        loadBadges();
        renderReservationsPage();
    } else {
        toast(res?.error || 'Failed', 'error');
    }
}

/* ============================================================ MENU */
async function renderMenuPage() {
    const data = await apiFetch('menu_items');
    if (!data) return;
    const cats = [...new Set(data.map(i=>i.category_name))];
    const filtered = menuFilter==='all' ? data : data.filter(i=>i.category_name===menuFilter);

    set('content',`
    <div class="filter-row">
      <div class="filter-chip ${menuFilter==='all'?'active':''}" onclick="setMenuFilter('all',this)">All</div>
      ${cats.map(c=>`<div class="filter-chip ${menuFilter===c?'active':''}" onclick="setMenuFilter('${c.replace(/'/g,"\\'")}',this)">${esc(c)}</div>`).join('')}
      <div class="filter-spacer"></div>
      <button class="btn btn-primary" onclick="openAddMenu()"><i class="fas fa-plus"></i> Add item</button>
    </div>
    <div class="menu-grid">${filtered.map(item=>`
    <div class="menu-card">
      <div class="menu-img">
        ${item.image ? `<img src="../images/menu/${esc(item.image)}" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none'"/>` : menuEmoji(item.category_name)}
        <div class="feat-badge" style="${item.is_featured==1?'':'display:none'}">${'Featured'}</div>
        <div class="avail-badge ${item.is_available==1?'avail-yes':'avail-no'}">${item.is_available==1?'Active':'Off'}</div>
      </div>
      <div class="menu-body">
        <div class="menu-name">${esc(item.name)}</div>
        <div class="menu-cat">${esc(item.category_name)}</div>
        <div class="menu-footer">
          <span class="menu-price">₹${parseFloat(item.price).toFixed(0)}</span>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="veg-dot" style="background:${item.is_veg==1?'#5DCAA5':'#f09595'}"></div>
            <div class="menu-actions">
              <button class="btn btn-ghost btn-sm" onclick='openEditMenu(${JSON.stringify(item)})'><i class="fas fa-edit"></i></button>
              <button class="btn btn-danger btn-sm" onclick="toggleAvail(${item.id},${item.is_available})"><i class="fas fa-${item.is_available==1?'eye-slash':'eye'}"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>`).join('')}
    ${!filtered.length?'<div style="grid-column:1/-1" class="empty-state"><i class="fas fa-utensils"></i><p>No items found</p></div>':''}</div>`);
}

function setMenuFilter(f, el) {
    menuFilter = f;
    document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    renderMenuPage();
}

async function openAddMenu() {
    const cats = await apiFetch('categories');
    document.getElementById('modalBox').innerHTML = menuForm(cats, null);
    openModal();
}

async function openEditMenu(item) {
    const cats = await apiFetch('categories');
    document.getElementById('modalBox').innerHTML = menuForm(cats, item);
    openModal();
}

function menuForm(cats, item) {
    const editing = !!item;
    return `<h3><i class="fas fa-${editing?'edit':'plus'}"></i> ${editing?'Edit':'Add'} Menu Item</h3>
    <div class="form-grid">
      <div class="form-group"><label>Item Name</label><input id="mName" value="${editing?esc(item.name):''}" placeholder="e.g. Paneer Tikka"/></div>
      <div class="form-group"><label>Category</label>
        <select id="mCat">${cats.map(c=>`<option value="${c.id}" ${editing&&item.category_id==c.id?'selected':''}>${esc(c.name)}</option>`).join('')}</select>
      </div>
      <div class="form-group"><label>Price (₹)</label><input id="mPrice" type="number" value="${editing?parseFloat(item.price).toFixed(0):''}" placeholder="220"/></div>
      <div class="form-group"><label>Type</label>
        <select id="mVeg"><option value="1" ${!editing||item.is_veg==1?'selected':''}>Vegetarian</option><option value="0" ${editing&&item.is_veg==0?'selected':''}>Non-Vegetarian</option></select>
      </div>
      <div class="form-group form-full"><label>Description</label><textarea id="mDesc" rows="2" placeholder="Short description...">${editing?esc(item.description||''):''}</textarea></div>
      <div class="form-group"><label>Image filename</label><input id="mImg" value="${editing?esc(item.image||''):''}" placeholder="paneer-tikka.jpg"/></div>
      <div class="form-group"><label>Featured</label>
        <select id="mFeat"><option value="0" ${!editing||item.is_featured==0?'selected':''}>No</option><option value="1" ${editing&&item.is_featured==1?'selected':''}>Yes – Chef's pick</option></select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveMenu(${editing?item.id:'null'})"><i class="fas fa-save"></i> Save</button>
    </div>`;
}

async function saveMenu(id) {
    const payload = {
        name:document.getElementById('mName').value.trim(),
        category_id:document.getElementById('mCat').value,
        price:document.getElementById('mPrice').value,
        is_veg:document.getElementById('mVeg').value,
        description:document.getElementById('mDesc').value.trim(),
        image:document.getElementById('mImg').value.trim(),
        is_featured:document.getElementById('mFeat').value,
    };
    if (!payload.name || !payload.price) { toast('Name and price are required','error'); return; }
    const action = id ? 'edit_menu_item' : 'add_menu_item';
    if (id) payload.item_id = id;
    const res = await apiPost(action, payload);
    if (res && res.success) {
        toast(id ? 'Item updated' : 'Item added', 'success');
        closeModal();
        renderMenuPage();
    } else {
        toast(res?.error || 'Failed', 'error');
    }
}

async function toggleAvail(id, current) {
    const res = await apiPost('toggle_availability', {item_id:id, is_available:current==1?0:1});
    if (res && res.success) {
        toast('Availability updated', 'success');
        renderMenuPage();
    }
}

/* ============================================================ CUSTOMERS */
async function renderCustomersPage() {
    const data = await apiFetch('customers');
    if (!data) return;
    const avatarColors = ['av-coral','av-blue','av-green','av-purple','av-amber','av-teal'];
    set('content',`
    <div class="card">
      <div class="card-header">
        <div class="card-title">All customers (${data.length})</div>
        <button class="card-action" onclick="exportCSV()"><i class="fas fa-download"></i> Export CSV</button>
      </div>
      ${data.map((c,i)=>`
      <div class="customer-row">
        <div class="avatar ${avatarColors[i%avatarColors.length]}">${esc(c.name.substring(0,2).toUpperCase())}</div>
        <div style="flex:1;min-width:0">
          <div class="cust-name">${esc(c.name)}</div>
          <div class="cust-meta">${esc(c.email)} &bull; ${esc(c.phone||'—')}</div>
        </div>
        <div style="text-align:right;flex-shrink:0;margin-left:12px">
          <div class="cust-stat-val">${c.total_orders} orders</div>
          <div class="cust-stat-label">₹${fmtNum(c.total_spent||0)} total</div>
        </div>
        <button class="btn btn-ghost btn-sm" style="margin-left:12px" onclick="viewCustomer(${c.id},'${esc(c.name)}')"><i class="fas fa-eye"></i></button>
      </div>`).join('')}
      ${!data.length?'<div class="empty-state"><i class="fas fa-users"></i><p>No customers yet</p></div>':''}
    </div>`);
}

async function viewCustomer(id, name) {
    const data = await apiFetch('customer_orders', {user_id:id});
    document.getElementById('modalBox').innerHTML = `
    <h3><i class="fas fa-user"></i> ${esc(name)}'s Orders</h3>
    ${data && data.length ? `<table class="admin-table">
      <thead><tr><th>ID</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>${data.map(o=>`<tr>
        <td class="bold">#${o.id}</td>
        <td>${fmtDate(o.created_at)}</td>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(o.items_summary||'—')}</td>
        <td class="bold">₹${fmtNum(o.total_amount)}</td>
        <td>${pill(o.status)}</td>
      </tr>`).join('')}</tbody>
    </table>` : '<div class="empty-state"><p>No orders found</p></div>'}
    <div class="form-actions"><button class="btn btn-ghost" onclick="closeModal()">Close</button></div>`;
    openModal();
}

function exportCSV() { window.open(API+'?action=export_customers','_blank'); }

/* ============================================================ REPORTS */
async function renderReportsPage() {
    const data = await apiFetch('reports');
    if (!data) return;
    const s = data.stats;
    set('content',`
    <div class="metrics">
      <div class="metric"><div class="metric-accent" style="background:#D85A30"></div>
        <div class="metric-label">Monthly revenue</div><div class="metric-value">₹${fmtNum(s.month_revenue)}</div>
        <div class="metric-trend trend-up"><i class="fas fa-chart-line"></i> ${s.month_orders} orders</div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#378ADD"></div>
        <div class="metric-label">Avg. order value</div><div class="metric-value">₹${fmtNum(s.avg_order)}</div>
        <div class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> This month</div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#1D9E75"></div>
        <div class="metric-label">Delivered</div><div class="metric-value">${s.delivered_count}</div>
        <div class="metric-trend trend-up"><i class="fas fa-check"></i> Successfully delivered</div>
      </div>
      <div class="metric"><div class="metric-accent" style="background:#E24B4A"></div>
        <div class="metric-label">Cancelled</div><div class="metric-value">${s.cancelled_count}</div>
        <div class="metric-trend trend-down"><i class="fas fa-times"></i> This month</div>
      </div>
    </div>
    <div class="grid2">
      <div class="card">
        <div class="card-header"><div class="card-title">Weekly revenue</div></div>
        <div class="week-chart" id="weekChart"></div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title">Peak hours</div></div>
        <div id="peakBars"></div>
      </div>
    </div>
    <div class="grid2">
      <div class="card">
        <div class="card-header"><div class="card-title">Monthly summary</div></div>
        <div class="kpi-row"><div class="kpi-label">Total orders</div><div class="kpi-val">${s.month_orders}</div></div>
        <div class="kpi-row"><div class="kpi-label">Total revenue</div><div class="kpi-val">₹${fmtNum(s.month_revenue)}</div></div>
        <div class="kpi-row"><div class="kpi-label">Avg. order value</div><div class="kpi-val">₹${fmtNum(s.avg_order)}</div></div>
        <div class="kpi-row"><div class="kpi-label">New customers</div><div class="kpi-val">${s.new_customers_month}</div></div>
        <div class="kpi-row"><div class="kpi-label">Reservations</div><div class="kpi-val">${s.month_reservations}</div></div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title">Top items this month</div></div>
        <div id="topItemsReport"></div>
      </div>
    </div>`);

    renderWeekChart(data.weekly);
    renderPeakBars(data.peak_hours);
    renderTopBars2(data.top_items);
}

function renderWeekChart(weekly) {
    if (!weekly || !weekly.length) return;
    const max = Math.max(...weekly.map(d=>parseFloat(d.revenue||0)))||1;
    const colors=['#FAECE7','#F0997B','#D85A30','#F0997B','#D85A30','#993C1D','#993C1D'];
    set('weekChart', weekly.map((d,i)=>`
    <div class="week-col">
      <div class="week-bar" style="height:0;background:${colors[i%colors.length]}" data-h="${(parseFloat(d.revenue||0)/max*90).toFixed(0)}" title="₹${fmtNum(d.revenue)}"></div>
      <div class="week-label">${d.day}</div>
    </div>`).join(''));
    setTimeout(()=>document.querySelectorAll('.week-bar').forEach(b=>b.style.height=b.dataset.h+'px'),50);
}

function renderPeakBars(peaks) {
    if (!peaks || !peaks.length) { set('peakBars','<div class="empty-state"><p>No data</p></div>'); return; }
    const max = Math.max(...peaks.map(p=>parseInt(p.count)));
    set('peakBars', peaks.map(p=>`
    <div class="bar-row">
      <div class="bar-label">${esc(p.label)}</div>
      <div class="bar-track"><div class="bar-fill" style="width:0%;background:#1D9E75" data-pct="${(p.count/max*100).toFixed(0)}"></div></div>
      <div class="bar-val">${p.count} orders</div>
    </div>`).join(''));
    setTimeout(()=>document.querySelectorAll('#peakBars .bar-fill').forEach(b=>b.style.width=b.dataset.pct+'%'),50);
}

function renderTopBars2(items) {
    if (!items || !items.length) { set('topItemsReport','<div class="empty-state"><p>No data</p></div>'); return; }
    const max = Math.max(...items.map(i=>parseFloat(i.revenue)));
    set('topItemsReport', items.map(i=>`
    <div class="bar-row">
      <div class="bar-label">${esc(i.name)}</div>
      <div class="bar-track"><div class="bar-fill" style="width:0%;background:#D85A30" data-pct="${(i.revenue/max*100).toFixed(0)}"></div></div>
      <div class="bar-val">₹${fmtNum(i.revenue)}</div>
    </div>`).join(''));
    setTimeout(()=>document.querySelectorAll('#topItemsReport .bar-fill').forEach(b=>b.style.width=b.dataset.pct+'%'),50);
}

/* ============================================================ MESSAGES */
async function renderMessagesPage() {
    const data = await apiFetch('messages');
    if (!data) return;
    set('content',`
    <div class="card">
      <div class="card-header"><div class="card-title">Contact messages (${data.length})</div></div>
      ${data.map(m=>`
      <div class="msg-card">
        <div class="msg-header">
          <div class="msg-from">${esc(m.name)}</div>
          <div class="msg-time">${fmtDate(m.created_at)}</div>
        </div>
        <div class="msg-email">${esc(m.email)}</div>
        ${m.subject?`<div class="msg-subject">${esc(m.subject)}</div>`:''}
        <div class="msg-body">${esc(m.message)}</div>
      </div>`).join('')}
      ${!data.length?'<div class="empty-state"><i class="fas fa-envelope-open"></i><p>No messages yet</p></div>':''}
    </div>`);
}

/* ============================================================ MODAL */
function openModal()  { document.getElementById('modal').classList.add('open'); }
function closeModal(e){ if (!e || e.target===document.getElementById('modal')) document.getElementById('modal').classList.remove('open'); }

/* ============================================================ TOAST */
function toast(msg, type='info') {
    const tc = document.getElementById('toastContainer');
    const t  = document.createElement('div');
    const icons = {success:'check-circle',error:'exclamation-circle',info:'info-circle'};
    t.className = `toast toast-${type}`;
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}"></i> ${esc(msg)}`;
    tc.appendChild(t);
    setTimeout(()=>{ t.classList.add('removing'); setTimeout(()=>t.remove(),300); }, 3500);
}

/* ============================================================ API */
async function apiFetch(action, params={}) {
    try {
        const q = new URLSearchParams({action,...params}).toString();
        const res = await fetch(`${API}?${q}`, {credentials:'include'});
        const data = await res.json();
        if (data.error && data.error.includes('Unauthorized')) {
            window.location.href = 'index.php';
            return null;
        }
        return data;
    } catch(e) { toast('Network error','error'); return null; }
}

async function apiPost(action, body={}) {
    try {
        const fd = new FormData();
        fd.append('action', action);
        Object.entries(body).forEach(([k,v])=>fd.append(k,v));
        const res = await fetch(API, {method:'POST', body:fd, credentials:'include'});
        return await res.json();
    } catch(e) { toast('Network error','error'); return null; }
}

/* ============================================================ HELPERS */
function set(id, html) { const el=document.getElementById(id); if(el) el.innerHTML=html; }
function esc(s) { if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtNum(n) { return Math.round(parseFloat(n||0)).toLocaleString('en-IN'); }
function fmtDate(d) { if(!d) return ''; return new Date(d).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
function fmtDateOnly(d) { if(!d) return ''; return new Date(d).toLocaleDateString('en-IN',{weekday:'short',day:'2-digit',month:'short',year:'numeric'}); }
function fmtTime(t) { if(!t) return ''; const [h,m]=t.split(':'); const hh=parseInt(h); return `${hh%12||12}:${m} ${hh>=12?'PM':'AM'}`; }
function fmtStatus(s) { const map={all:'All',pending:'Pending',confirmed:'Confirmed',preparing:'Preparing',out_for_delivery:'Out for delivery',delivered:'Delivered',cancelled:'Cancelled',completed:'Completed'}; return map[s]||s; }
function pill(s) { return `<span class="pill pill-${s}">${fmtStatus(s)}</span>`; }
function menuEmoji(cat) { const m={'Starters':'🥗','Main Course':'🍛','Breads':'🫓','Rice & Biryani':'🍚','Desserts':'🍮','Beverages':'🥤'}; return m[cat]||'🍽️'; }
function debounce(fn, ms) { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }

/* ============================================================ SITE SETTINGS */
async function renderSettingsPage() {
    const data = await apiFetch('get_settings');

    if (!data || data.error) {
        set('content', `<div class="empty-state" style="padding:80px 20px">
            <i class="fas fa-cog" style="font-size:36px;margin-bottom:12px;display:block;color:#f09595"></i>
            <p style="font-size:14px">${esc(data?.error || 'Failed to load settings')}</p>
            <p style="font-size:12px;color:rgba(255,255,255,0.3);margin:10px 0 16px">Run <strong>settings_setup.sql</strong> in phpMyAdmin first.</p>
            <button class="btn btn-ghost" onclick="loadPage('settings')"><i class="fas fa-sync-alt"></i> Retry</button>
        </div>`);
        return;
    }

    // Group settings
    const groups = {};
    data.forEach(s => {
        if (!groups[s.group_name]) groups[s.group_name] = [];
        groups[s.group_name].push(s);
    });

    const groupLabels = {
        general: '🏠  General',
        contact: '📞  Contact & Location',
        hours:   '🕐  Opening Hours',
        social:  '🌐  Social Media Links',
        seo:     '🔍  SEO & Maps',
    };

    const groupOrder = ['general','contact','hours','social','seo'];

    set('content', `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <p style="font-size:13px;color:rgba(255,255,255,0.4)">Changes are live on the website immediately after saving.</p>
      <button class="btn btn-primary" onclick="saveAllSettings()"><i class="fas fa-save"></i> Save All Changes</button>
    </div>
    <div id="settingsAccordion">
      ${groupOrder.filter(g => groups[g]).map(g => `
      <div class="card" style="margin-bottom:12px">
        <div class="card-header" style="cursor:pointer" onclick="toggleGroup('grp-${g}')">
          <div class="card-title">${groupLabels[g] || g}</div>
          <i class="fas fa-chevron-down" id="icon-${g}" style="color:rgba(255,255,255,0.3);transition:transform 0.2s"></i>
        </div>
        <div id="grp-${g}" style="display:block">
          <div class="form-grid" style="margin-top:4px">
            ${groups[g].map(s => `
            <div class="form-group ${s.setting_key === 'about_text_1' || s.setting_key === 'about_text_2' || s.setting_key === 'meta_description' || s.setting_key === 'google_maps_embed' ? 'form-full' : ''}">
              <label>${esc(s.label)}</label>
              ${s.setting_key === 'about_text_1' || s.setting_key === 'about_text_2' || s.setting_key === 'meta_description'
                ? `<textarea id="set_${esc(s.setting_key)}" rows="3" data-key="${esc(s.setting_key)}">${esc(s.setting_val || '')}</textarea>`
                : s.setting_key === 'google_maps_embed'
                ? `<input id="set_${esc(s.setting_key)}" type="url" value="${esc(s.setting_val || '')}" data-key="${esc(s.setting_key)}" placeholder="https://www.google.com/maps/embed?pb=..."/>`
                : `<input id="set_${esc(s.setting_key)}" type="text" value="${esc(s.setting_val || '')}" data-key="${esc(s.setting_key)}"/>`
              }
            </div>`).join('')}
          </div>
        </div>
      </div>`).join('')}
    </div>
    <div style="display:flex;justify-content:flex-end;margin-top:6px">
      <button class="btn btn-primary btn-lg" onclick="saveAllSettings()"><i class="fas fa-save"></i> Save All Changes</button>
    </div>`);
}

function toggleGroup(id) {
    const el   = document.getElementById(id);
    const key  = id.replace('grp-', '');
    const icon = document.getElementById('icon-' + key);
    if (!el) return;
    const open = el.style.display !== 'none';
    el.style.display = open ? 'none' : 'block';
    if (icon) icon.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}

async function saveAllSettings() {
    const inputs = document.querySelectorAll('[data-key]');
    const settings = {};
    inputs.forEach(el => { settings[el.dataset.key] = el.value; });

    const btn = document.querySelector('.btn-primary[onclick="saveAllSettings()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; }

    const fd = new FormData();
    fd.append('action', 'save_settings');
    fd.append('settings', JSON.stringify(settings));

    try {
        const res  = await fetch(API, { method: 'POST', body: fd, credentials: 'include' });
        const data = await res.json();
        if (data.success) {
            toast(`✅ ${data.message}`, 'success');
        } else {
            toast(data.error || 'Failed to save', 'error');
        }
    } catch(e) {
        toast('Network error', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save All Changes'; }
    }
}
