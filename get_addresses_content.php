<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">User not logged in</div>';
    exit();
}

// This file returns an HTML fragment for the addresses management UI.
// It will fetch address data via the existing `get_addresses.php` (JSON) endpoint
// and call add_address.php / update_address.php / delete_address.php for actions.
?>

<div class="addresses-section">
  <h2>My Addresses</h2>
  <div id="addresses-list">Loading addresses...</div>
  <hr />
  <h3>Add New Address</h3>
  <form id="addAddressForm">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="form-group">
      <label>Flat / House</label>
      <input type="text" name="flat_house">
    </div>
    <div class="form-group">
      <label>Building / Apartment</label>
      <input type="text" name="building_apartment">
    </div>
    <div class="form-group">
      <label>Street / Road</label>
      <input type="text" name="street_road" placeholder="Optional">
    </div>
    <div class="form-group">
      <label>Landmark</label>
      <input type="text" name="landmark" placeholder="Optional">
    </div>
    <div class="form-group">
      <label>Area / Locality</label>
      <input type="text" name="area_locality" placeholder="Optional">
    </div>
    <div class="form-group">
      <label>Pincode</label>
      <input type="text" name="pincode" required>
    </div>
    <div class="form-group">
      <label>District</label>
      <input type="text" name="district" required>
    </div>
    <div class="form-group">
      <label>City</label>
      <input type="text" name="city" value="Delhi">
    </div>
    <div class="form-group">
      <label>State</label>
      <input type="text" name="state" value="Delhi (NCT)">
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_default"> Set as default</label>
    </div>
    <button type="submit" class="btn green">Add Address</button>
  </form>
</div>

<style>
.addresses-section { background: #fff; padding: 20px; border-radius: 8px; }
.address-card { border: 1px solid #e6e6e6; padding: 12px; border-radius: 6px; margin-bottom: 10px; }
.address-actions { margin-top: 8px; }
.address-actions button { margin-right: 8px; }
.form-group { margin-bottom: 10px; }
.form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
</style>

<script>
// CSRF token from PHP session (fragment executed server-side)
const csrfToken = '<?php echo $_SESSION["csrf_token"] ?? "" ?>';

function renderAddresses(addresses) {
  const container = document.getElementById('addresses-list');
  if (!addresses || addresses.length === 0) {
    container.innerHTML = '<p>No addresses found.</p>';
    return;
  }

  container.innerHTML = '';
  addresses.forEach(addr => {
    const div = document.createElement('div');
    div.className = 'address-card';
    div.id = 'address-' + addr.id;

    div.innerHTML = `
      <div><strong>${escapeHtml(addr.area_locality || '')} ${addr.flat_house ? (' - ' + escapeHtml(addr.flat_house)) : ''}</strong></div>
      <div>${escapeHtml(addr.street_road || '')}</div>
      <div>${escapeHtml(addr.district || '')} - ${escapeHtml(addr.pincode || '')}</div>
      <div>${escapeHtml(addr.city || '')}, ${escapeHtml(addr.state || '')}</div>
      <div class="address-actions">
        <button class="btn" onclick="showEditForm(${addr.id})">Edit</button>
        <button class="delete-btn" onclick="removeAddress(${addr.id})">Delete</button>
        ${addr.is_default == 1 ? '<span style="margin-left:8px;color:green;font-weight:bold;">Default</span>' : ''}
      </div>
      <div class="edit-area" id="edit-area-${addr.id}" style="display:none; margin-top:10px;"></div>
    `;

    container.appendChild(div);
  });
}

function escapeHtml(s) { return (s+'').replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }

function loadAddresses() {
  fetch('get_addresses.php')
    .then(r => r.json())
    .then(data => {
      if (data.success) renderAddresses(data.addresses);
      else document.getElementById('addresses-list').innerHTML = '<p>Error loading addresses.</p>';
    })
    .catch(err => {
      console.error(err);
      document.getElementById('addresses-list').innerHTML = '<p>Error loading addresses.</p>';
    });
}

function showEditForm(id) {
  const editArea = document.getElementById('edit-area-' + id);
  if (!editArea) return;
  // If already populated toggle
  if (editArea.innerHTML.trim() !== '') {
    editArea.style.display = editArea.style.display === 'block' ? 'none' : 'block';
    return;
  }

  // build edit form by fetching the address data from the existing list
  const card = document.getElementById('address-' + id);
  const addr = Array.from(document.querySelectorAll('#addresses-list .address-card')).map(c=>{
    // noop placeholder
  });

  // get details from server to be safe
  fetch('get_addresses.php')
    .then(r=>r.json())
    .then(data=>{
      if (!data.success) return alert('Failed to load address');
      const item = data.addresses.find(a=>a.id==id);
      if (!item) return alert('Address not found');

      editArea.innerHTML = `
        <form onsubmit="submitEdit(event, ${id})">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
          <div class=\"form-group\"><label>Flat / House</label><input name=\"flat_house\" value=\"${escapeHtml(item.flat_house || '')}\"></div>
          <div class=\"form-group\"><label>Building / Apartment</label><input name=\"building_apartment\" value=\"${escapeHtml(item.building_apartment || '')}\"></div>
          <div class=\"form-group\"><label>Street / Road</label><input name=\"street_road\" value=\"${escapeHtml(item.street_road || '')}\"></div>
          <div class=\"form-group\"><label>Landmark</label><input name=\"landmark\" value=\"${escapeHtml(item.landmark || '')}\"></div>
          <div class=\"form-group\"><label>Area / Locality</label><input name=\"area_locality\" value=\"${escapeHtml(item.area_locality || '')}\"></div>
          <div class=\"form-group\"><label>Pincode</label><input name=\"pincode\" value=\"${escapeHtml(item.pincode || '')}\" required></div>
          <div class=\"form-group\"><label>District</label><input name=\"district\" value=\"${escapeHtml(item.district || '')}\" required></div>
          <div class=\"form-group\"><label>City</label><input name=\"city\" value=\"${escapeHtml(item.city || '')}\"></div>
          <div class=\"form-group\"><label>State</label><input name=\"state\" value=\"${escapeHtml(item.state || '')}\"></div>
          <div class=\"form-group\"><label><input type=\"checkbox\" name=\"is_default\" ${item.is_default==1? 'checked' : ''}> Set as default</label></div>
          <button class=\"btn green\" type=\"submit\">Save</button>
          <button type=\"button\" class=\"btn\" onclick=\"editAreaClose(${id})\">Cancel</button>
        </form>
      `;
      editArea.style.display = 'block';
    })
}

function editAreaClose(id){ const a=document.getElementById('edit-area-'+id); if(a){ a.style.display='none'; }}

function submitEdit(e, id) {
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  if (!fd.get('csrf_token')) fd.append('csrf_token', csrfToken);
  fd.append('address_id', id);

  fetch('update_address.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(data=>{
      if (data.success) { alert('Address updated'); loadAddresses(); }
      else alert('Error: '+data.message);
    })
    .catch(err=>{ console.error(err); alert('Error'); });
}

function removeAddress(id) {
  if (!confirm('Delete this address?')) return;
  const fd = new FormData(); fd.append('address_id', id);
  fd.append('csrf_token', csrfToken);
  fetch('delete_address.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(data=>{
      if (data.success) { alert('Deleted'); loadAddresses(); }
      else alert('Error: '+data.message);
    })
    .catch(err=>{ console.error(err); alert('Error'); });
}

// add address form handler
document.addEventListener('submit', function(e){
  if (e.target && e.target.id === 'addAddressForm'){
    e.preventDefault();
    const form = e.target; const fd = new FormData(form);
    // ensure csrf token (already present as hidden input, but ensure fallback)
    if (!fd.get('csrf_token')) fd.append('csrf_token', csrfToken);
    fetch('add_address.php', { method: 'POST', body: fd })
      .then(r=>r.json())
      .then(data=>{
        if (data.success) {
          alert('Address added');
          form.reset();
          // If server returned the new address object, append it to list, else reload
          if (data.address) {
            loadAddresses(); // simpler: reload list to keep ordering consistent
          } else {
            loadAddresses();
          }
        } else alert('Error: '+data.message);
      })
      .catch(err=>{ console.error(err); alert('Error'); });
  }
});

// initial load
loadAddresses();
</script>
