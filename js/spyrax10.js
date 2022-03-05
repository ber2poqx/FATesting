//Added by spyrax10

//#region Misc
function read_only($name, $readOnly=false) {
    document.getElementsByName($name)[0].disabled = $readOnly;
}
//#endregion Misc

//#region sweetalert2

const top_end_toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    onOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

const top_center_toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 5000,
});

const bootstrap_button = Swal.mixin({
    customClass: {
      confirmButton: 'btn btn-success',
      cancelButton: 'btn btn-danger'
    },
    buttonsStyling: false
});

function confirm_dialog($suc_msg = '') {

    if ($suc_msg == '') {
        $suc_msg = 'Transaction has been process!';
    }

    Swal.fire({
        title: 'Proceed with this Transaction?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Proceed!',
        cancelButtonText: 'No, cancel!',
        cancelButtonColor: '#f70505',
        allowOutsideClick: false,
        reverseButtons: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Success!', $suc_msg, 'success');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Terminated!', 'Transaction Canceled!', 'error');
        }
    });
}

function display_popup_msg($title, $msg, $icon = '') {

    $title = $title == '' ? "Can't Proceed!" : $title;
    $icon = $icon == '' ? 'warning' : $icon;

    Swal.fire($title, $msg, $icon);
}

function display_info_msg($msg, $align = 'center') {
    if ($align == 'end') {
        top_end_toast.fire({
            title: $msg,
            icon: 'success'
        });
    }
    else {
        top_center_toast.fire({
            title: $msg,
            icon: 'success'
        });
    }
}

function display_error_msg($msg, $align = 'center') {
    if ($align == 'end') {
        top_end_toast.fire({
            title: $msg,
            icon: 'success'
        });
    }
    else {
        top_center_toast.fire({
            title: $msg,
            icon: 'success'
        });
    }
}

//#endregion sweetalert2