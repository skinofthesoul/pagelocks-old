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
            const lockList = await response.json();
            this.displayLocks(lockList);
        }
        else {
            alert('No valid response from server.');
        }
    }
    displayLocks(locks) {
        const tableRows = document.getElementById('locklist');
        tableRows.innerHTML = '';
        Object.keys(locks).forEach((email, i) => {
            const lock = locks[email];
            const date = new Date(lock.timestamp * 1000);
            const lockRow = document.createElement('tr');
            lockRow.innerHTML = `
            <td class="author">${lock.fullname}</td>
            <td class="route">${lock.route}</td>
            <td class="since">${date.toLocaleTimeString()}</td>
            <td id="row${i}" class="delete"><a><i class="fa fa-close"></i></a></td>
            `;
            tableRows.appendChild(lockRow);
            document.getElementById(`row${i}`).addEventListener('click', () => {
                if (window.confirm(`Have you confirmed the page is no longer being edited by ${lock.fullname}?`)) {
                    this.removeLock(email);
                }
            });
        });
    }
    async removeLock(email) {
        let response;
        try {
            const data = new FormData();
            data.append('removeLock', email);
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
            this.readLocks();
        }
        else {
            alert('No valid response from server.');
        }
    }
}
const admin = new PageLocksAdmin();
admin.readLocks();
//# sourceMappingURL=pagelockadmin.js.map