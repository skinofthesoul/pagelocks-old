interface AcquireLockAnswer {
    isOnPage: boolean,
    isLockGranted: boolean;
    byUser: string;
    keepAliveInterval: number;
    alert: string;
}

interface RemoveLockAnswer {
    isRemoved: boolean;
    alert: string;
}

interface KeepAliveAnswer {
    isExtended: boolean;
    alert: string;
}

/**
 * Sends locks requests to server and when lock has been acquired starts keepalive loop to keep lock.
 */
class PageLocker {

    /**
     * Used by keepAlive to stop/continue extending lock
     */
    protected isLockGranted = true;

    /**
     * Request a lock on current page
     */
    public async acquireLock(): Promise<void> {
        const data = new FormData();
        data.append('acquireLock', window.location.pathname);

        let response: Response;

        try {
            response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data,
            });
        } catch (error) {
            alert('Unexpected error while accessing the server.');
            return;
        }

        if (response.ok) {
            const answer = await response.json() as AcquireLockAnswer;

            if (answer.isOnPage) {
                this.isLockGranted = answer.isLockGranted;

                if (answer.isLockGranted) {
                    this.keepAlive(answer.keepAliveInterval);
                } else {
                    this.alert(answer.alert, 'error')
                }

                this.setLockingUI();
            }
        } else {
            alert('No valid response from server.');
        }
    }

    /**
     * Create layer on top of form to lock form
     */
    protected setLockingUI() {
        const elements = document.querySelectorAll(
            'form#blueprints .tabs-content, #titlebar-button-delete, #titlebar-save, #admin-mode-toggle');
        elements.forEach((element: HTMLElement) => {
            if (this.isLockGranted) {
                element.classList.remove('locked');
            } else {
                element.classList.add('locked');
            }

        });
    }

    /**
     * Runs keepalive loop
     */
    protected keepAlive(interval: number) {
        if (!this.isLockGranted) {
            return;
        }

        setTimeout(async () => {
            const data = new FormData();
            data.append('keepAlive', window.location.pathname);

            let response: Response;

            try {
                response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: data,
                });
            } catch (error) {
                alert('Unexpected error while accessing the server.');
                return;
            }

            if (response.ok) {
                const answer = await response.json() as KeepAliveAnswer;

                if (answer.isExtended) {
                    this.keepAlive(interval);
                } else {
                    this.alert(answer.alert, 'error');
                }
            } else {
                alert('No valid response from server.');
            }

        }, interval);
    }

    /**
     * Show alert banner in top of page
     */
    public alert(message: string, type: 'info' | 'error') {
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
    public clearAlert() {
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
