const URL = "/one-api/mockup/purchase/transfer/";

export async function getBranches(pload) {
    try {
        var response = await axios.post(URL + "TransferRequest/getBranches", pload);

        if (response.status !== 200) {
            return {
                status: "ERR",
                message: response.statusText
            }
        }

        let data = response.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        }
    }
}

export async function getStatus(pload) {
    try {
        var response = await axios.post(URL + "TransferRequest/getStatus", pload);

        if (response.status !== 200) {
            return {
                status: "ERR",
                message: response.statusText
            }
        }

        let data = response.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        }
    }
}

export async function search(pload) {
    try {
        var response = await axios.post(URL + "TransferRequest/search", pload);

        if (response.status !== 200) {
            return {
                status: "ERR",
                message: response.statusText
            }
        }

        let data = response.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        }
    }
}

export async function saveRequest(pload) {
    try {
        var response = await axios.post(URL + "TransferRequest/createTfRequest", pload);

        if (response.status !== 200) {
            return {
                status: "ERR",
                message: response.statusText
            }
        }

        let data = response.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        }
    }
}