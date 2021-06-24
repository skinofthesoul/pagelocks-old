interface Lock {
    fullname: string;
    route: string;
    timestamp: number;
}

interface Locks {
    locks: {
        email: Lock;
    };
    alert: string;
    countAlert: string;
}

interface Answer {
    isLockRemoved: boolean;
    alert: string;
}

class PageLocksAdmin {
    public async readLocks() {
        let response: Response;

        try {
            const data = new FormData();
            data.append('readLocks', '');

            response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data,
            });
        } catch (error) {
            alert('Unexpected error while accessing the server.');
            return;
        }

        if (response.ok) {
            const answer = await response.json() as Locks;

            if (Object.keys(answer.locks).length === 0) {
                this.alert(answer.countAlert, 'info');
            }

            this.displayLocks(answer);
        } else {
            alert('No valid response from server.');
        }
    }

    private displayLocks(data: Locks) {
        const tableRows = document.getElementById('locklist');
        tableRows.innerHTML = '';

        Object.keys(data.locks).forEach((route: string, i: number) => {
            const lock = data.locks[route];
            const date: Date = new Date(lock.timestamp * 1000);

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
            })
        });
    }

    public async removeLock(route: string) {
        let response: Response;

        try {
            const data = new FormData();
            data.append('forceRemoveLock', route);
    
            response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data,
            });
        } catch (error) {
            alert('Unexpected error while accessing the server.');
            return;
        }

        this.clearAlerts();

        if (response.ok) {
            const answer = await response.json() as Answer;

            if (answer.isLockRemoved) {
                this.alert(answer.alert, 'info');
            } else {
                this.alert(answer.alert, 'error');
            }

            this.readLocks();
        } else {
            alert('No valid response from server.');
        }
    }
    
    public alert(message: string, type: 'info'|'error') {
        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert pagelocks`;

        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);

        const messages = document.getElementById('messages');
        messages.appendChild(newMessage);
    }

    public clearAlerts() {
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