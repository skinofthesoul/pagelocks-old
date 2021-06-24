class PageLocksAdmin {
    async readLocks() {
        let response;
        try {
            const data = new FormData();
            data.append('readLocks', '');
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
            if (Object.keys(answer.locks).length === 0) {
                this.alert(answer.countAlert, 'info');
            }
            this.displayLocks(answer);
        }
        else {
            alert('No valid response from server.');
        }
    }
    displayLocks(data) {
        const tableRows = document.getElementById('locklist');
        tableRows.innerHTML = '';
        Object.keys(data.locks).forEach((route, i) => {
            const lock = data.locks[route];
            const date = new Date(lock.timestamp * 1000);
            const lockRow = document.createElement('tr');
            lockRow.innerHTML = `
            <td class="author">${lock.fullname}</td>
            <td class="route">${route}</td>
            <td class="since">${date.toLocaleTimeString()}</td>
            <td id="row${i}" class="delete">
                <a href="#delete" 
                    class="page-delete delete-action" 
                    title="Delete Item"
                    >
                    <i class="fa fa-close"></i>
                </a>
            </td>
            `;
            tableRows.appendChild(lockRow);
            document.getElementById(`row${i}`).addEventListener('click', () => {
                if (window.confirm(data.alert.replace('%s', lock.fullname))) {
                    this.removeLock(route);
                }
            });
        });
    }
    async removeLock(route) {
        let response;
        try {
            const data = new FormData();
            data.append('forceRemoveLock', route);
            response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data,
            });
        }
        catch (error) {
            alert('Unexpected error while accessing the server.');
            return;
        }
        this.clearAlerts();
        if (response.ok) {
            const answer = await response.json();
            if (answer.isLockRemoved) {
                this.alert(answer.alert, 'info');
            }
            else {
                this.alert(answer.alert, 'error');
            }
            this.readLocks();
        }
        else {
            alert('No valid response from server.');
        }
    }
    alert(message, type) {
        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert pagelocks`;
        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);
        const messages = document.getElementById('messages');
        messages.appendChild(newMessage);
    }
    clearAlerts() {
        const alerts = document.getElementsByClassName('alert pagelocks');
        for (let alert of alerts) {
            const messages = document.getElementById('messages');
            messages.removeChild(alert);
        }
    }
}
const admin = new PageLocksAdmin();
admin.clearAlerts();
admin.readLocks();
//# sourceMappingURL=pagelocksadmin.js.map