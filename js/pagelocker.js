/**
 * Sends locks requests to server and when lock has been acquired starts keepalive loop to keep lock.
 */
class PageLocker {
    constructor() {
        /**
         * Used by keepAlive to stop/continue extending lock
         */
        this.isLockGranted = true;
    }
    /**
     * Request a lock on current page
     */
    async acquireLock() {
        const data = new FormData();
        data.append('acquireLock', window.location.pathname);
        let response;
        try {
            response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data,
            });
        }
        catch (error) {
            alert('Unexpected error while accessing the server.');
            return;
        }
        if (response.ok) {
            const answer = await response.json();
            if (answer.isOnPage) {
                this.isLockGranted = answer.isLockGranted;
                if (answer.isLockGranted) {
                    this.keepAlive(answer.keepAliveInterval);
                }
                else {
                    this.alert(answer.alert, 'error');
                }
                this.setLockingUI();
            }
        }
        else {
            alert('No valid response from server.');
        }
    }
    /**
     * Create layer on top of form to lock form
     */
    setLockingUI() {
        const elements = document.querySelectorAll('form#blueprints .tabs-content, #titlebar-button-delete, #titlebar-save, #admin-mode-toggle');
        elements.forEach((element) => {
            if (this.isLockGranted) {
                element.classList.remove('locked');
            }
            else {
                element.classList.add('locked');
            }
        });
    }
    /**
     * Runs keepalive loop
     */
    keepAlive(interval) {
        if (!this.isLockGranted) {
            return;
        }
        setTimeout(async () => {
            const data = new FormData();
            data.append('keepAlive', window.location.pathname);
            let response;
            try {
                response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: data,
                });
            }
            catch (error) {
                alert('Unexpected error while accessing the server.');
                return;
            }
            if (response.ok) {
                const answer = await response.json();
                if (answer.isExtended) {
                    this.keepAlive(interval);
                }
                else {
                    this.alert(answer.alert, 'error');
                }
            }
            else {
                alert('No valid response from server.');
            }
        }, interval);
    }
    /**
     * Show alert banner in top of page
     */
    alert(message, type) {
        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert pagelocks`;
        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);
        const messages = document.getElementById('messages');
        messages.appendChild(newMessage);
    }
    /**
     * Clears alert banner
     */
    clearAlert() {
        const alerts = document.getElementsByClassName('alert pagelocks');
        for (let alert of alerts) {
            const messages = document.getElementById('messages');
            messages.removeChild(alert);
        }
    }
}
const pageLocker = new PageLocker();
pageLocker.clearAlert();
pageLocker.acquireLock();
//# sourceMappingURL=pagelocker.js.map