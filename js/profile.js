$(document).ready(function () {

  const token = localStorage.getItem('authToken');

  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  let lastLoadedProfile = null;

  loadProfile();
/*$('#editBtn').click(function () {
    $(this).addClass('d-none');
    $('.saveprofileBlock').removeClass('d-none'); 
  });*/
  $('#updateBtn').on('click', handleUpdate);
  $('#resetBtn').on('click', function () {
    if (lastLoadedProfile) {
      populateForm(lastLoadedProfile);
    }
    hideAlert();
  });
  $('#logoutBtn').on('click', handleLogout);

  function loadProfile() {
    $.ajax({
      url: 'php/profile_get.php',
      type: 'GET',
      dataType: 'json',
      headers: { 'Authorization': 'Bearer ' + token }
    })
      .done(function (response) {
        if (response && response.success) {
          lastLoadedProfile = response.data;
          populateForm(response.data);
        } else {
          showAlert((response && response.message) || 'Could not load your profile.', 'danger');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 401) {
          forceLogout();
          return;
        }
        showAlert('Could not load your profile. Please refresh the page.', 'danger');
      });
  }

  function populateForm(user) {
    $('#username').val(user.username || '');
    $('#email').val(user.email || '');
    $('#fullName').val(user.full_name || '');
    $('#age').val(user.age !== null && user.age !== undefined ? user.age : '');
    $('#dob').val(user.dob || '');
    $('#contact').val(user.contact || '');
    $('#address').val(user.address || '');

    $('#displayName').text(user.full_name || user.username || 'Your profile');
    $('#displayUsername').text('@' + (user.username || ''));
    $('#avatarInitials').text(getInitials(user.full_name || user.username));
  }

  function getInitials(name) {
    if (!name) { return '--'; }
    const parts = name.trim().split(/\s+/);
    const initials = parts.slice(0, 2).map(function (p) { return p.charAt(0).toUpperCase(); });
    return initials.join('') || '--';
  }

  function handleUpdate() {
    hideAlert();

    const fullName = $('#fullName').val().trim();
    const age = $('#age').val();
    const dob = $('#dob').val();
    const contact = $('#contact').val().trim();
    const address = $('#address').val().trim();

    if (!fullName) {
      showAlert('Full name cannot be empty.', 'danger');
      return;
    }

    if (age !== '' && (isNaN(age) || age < 0 || age > 130)) {
      showAlert('Enter a valid age between 0 and 130.', 'danger');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/profile_update.php',
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      headers: { 'Authorization': 'Bearer ' + token },
      data: JSON.stringify({
        full_name: fullName,
        age: age,
        dob: dob,
        contact: contact,
        address: address
      })
    })
      .done(function (response) {
        if (response && response.success) {
          showAlert(response.message || 'Profile updated.', 'success');
          loadProfile();
        } else {
          showAlert((response && response.message) || 'Update could not be completed.', 'danger');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 401) {
          forceLogout();
          return;
        }
        showAlert(extractError(xhr, 'Update failed. Please try again.'), 'danger');
      })
      .always(function () {
        setLoading(false);
      });
  }

  function handleLogout() {
    $.ajax({
      url: 'php/logout.php',
      type: 'POST',
      dataType: 'json',
      headers: { 'Authorization': 'Bearer ' + token }
    }).always(function () {
      forceLogout();
    });
  }

  function forceLogout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('authUser');
    window.location.href = 'login.html';
  }

  function setLoading(isLoading) {
    $('#updateBtn')
      .prop('disabled', isLoading)
      .text(isLoading ? 'Saving…' : 'Save changes');
  }

  function extractError(xhr, fallback) {
    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
      return xhr.responseJSON.message;
    }
    return fallback;
  }

  function showAlert(message, type) {
    $('#profileAlertBox')
      .removeClass('d-none alert-success alert-danger')
      .addClass('alert-' + type)
      .text(message);
  }

  function hideAlert() {
    $('#profileAlertBox').addClass('d-none').text('');
  }

});
