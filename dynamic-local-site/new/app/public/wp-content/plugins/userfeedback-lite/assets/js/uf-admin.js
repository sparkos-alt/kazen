(function() {
    const addUpgradeItemHighlight = function() {
        const submenuItem = document.querySelector( '.userfeedback-upgrade-submenu' );

        if ( submenuItem ) {
            const li = submenuItem.parentNode.parentNode;

            if ( li ) {
                li.classList.add( 'userfeedback-submenu-highlight' );
            }

            const parentLink = submenuItem.closest('a');
            parentLink.setAttribute('target', '_blank');
        }
    }

    addUpgradeItemHighlight();
})()


// open notifications drawer on admin menu indicator click
window.addEventListener('load', function() {
    let indicator = document.querySelector('.userfeedback-notifications-indicator');
    if(!indicator) return;
    indicator.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        location.href = window.userfeedback.admin_url+'admin.php?page=userfeedback_surveys&open=userfeedback_notifications';
    });
});