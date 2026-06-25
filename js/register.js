$(document).ready(function () {

  $('#registerForm').on('submit', function (e) {
    e.preventDefault(); // belt-and-suspenders: never let this form actually submit
  });

  $('#registerBtn').on('click', handleRegister);

  function handleRegister() {
    hideAlert();

    const fullName = $('#fullName').val().trim();
    const username = $('#username').val().trim();
    const email = $('#email').val().trim();
    const password = $('#password').val();
    const confirmPassword = $('#confirmPassword').val();

    if (!fullName || !username || !email || !password || !confirmPassword) {
      showAlert('Please fill in every field.', 'danger');
      return;
    }

    if (password !== confirmPassword) {
      showAlert('Passwords do not match.', 'danger');
      return;
    }

    if (password.length < 6) {
      showAlert('Password must be at least 6 characters long.', 'danger');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/register.php',
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        full_name: fullName,
        username: username,
        email: email,
        password: password
      })
    })
      .done(function (response) {
        if (response && response.success) {
          showAlert('Account created. Redirecting to sign in…', 'success');
          setTimeout(function () {
            window.location.href = 'login.html';
          }, 1200);
        } else {
          showAlert((response && response.message) || 'Registration could not be completed.', 'danger');
          setLoading(false);
        }
      })
      .fail(function (xhr) {
        showAlert(extractError(xhr, 'Registration failed. Please try again.'), 'danger');
        setLoading(false);
      });
  }

  function setLoading(isLoading) {
    $('#registerBtn')
      .prop('disabled', isLoading)
      .text(isLoading ? 'Creating account…' : 'Create account');
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
