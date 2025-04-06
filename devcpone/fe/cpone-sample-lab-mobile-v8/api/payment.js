const URL = "/one-api/mockup/fo/cashier/";

export async function lookup_type(token) {
    try {
        var resp = await axios.post(URL + 'payment/lookup_type',{token:token});
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}

export async function pay(prm) {
    try {
        var resp = await axios.post(URL + 'payment/pay',prm);
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}

export async function delete_note(prm) {
    try {
        var resp = await axios.post(URL + 'payment/delete_note',prm);
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}
