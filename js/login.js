$(document).ready(function () {

  // already signed in? skip straight to the profile
  if (localStorage.getItem('authToken')) {
    window.location.href = 'profile.html';
    return;
  }

  $('#loginForm').on('submit', function (e) {
    e.preventDefault(); // never let this form actually submit
  });

  $('#loginBtn').on('click', handleLogin);

  function handleLogin() {
    hideAlert();

    const usernameOrEmail = $('#usernameOrEmail').val().trim();
    const password = $('#password').val();

    if (!usernameOrEmail || !password) {
      showAlert('Enter your username/email and password.', 'danger');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/login.php',
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        username: usernameOrEmail,
        password: password
      })
    })
      .done(function (response) {
        if (response && response.success) {
          localStorage.setItem('authToken', response.token);
          localStorage.setItem('authUser', JSON.stringify(response.user));
          window.location.href = 'profile.html';
        } else {
          showAlert((response && response.message) || 'Invalid credentials.', 'danger');
          setLoading(false);
        }
      })
      .fail(function (xhr) {
        showAlert(extractError(xhr, 'Sign in failed. Please try again.'), 'danger');
        setLoading(false);
      });
  }

  function setLoading(isLoading) {
    $('#loginBtn')
      .prop('disabled', isLoading)
      .text(isLoading ? 'Signing in…' : 'Sign in');
  }

  function extractError(xhr, fallback) {
    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
      return xhr.responseJSON.message;
    }
    return fallback;
  }

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger')
      .addClass('alert-' + type)
      .text(message);
  }

  function hideAlert() {
    $('#alertBox').addClass('d-none').text('');
  }

});
