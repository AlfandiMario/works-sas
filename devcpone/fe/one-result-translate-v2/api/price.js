const URL = "/one-api/mockup/mdprice/mdprice/";

// https://devcpone.aplikasi.web.id/one-api/mockup/mdprice/mdprice/searchpriceheader/

export async function search(prm) {
    try {
        var resp = await axios.post(URL + 'searchpriceheader', prm);
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
export async function insertheader(prm) {
    try {
        var resp = await axios.post(URL + 'insertpriceheader', prm);
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
export async function editheader(prm) {
    try {
        var resp = await axios.post(URL + 'editpriceheader', prm);
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
export async function deleteheader(prm) {
    try {
        var resp = await axios.post(URL + 'deletepriceheader', prm);
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
export async function getfilterprice(prm) {
    try {
        var resp = await axios.post(URL + 'getfilterprice', prm);
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
export async function searchpricetest(prm) {
    try {
        var resp = await axios.post(URL + 'searchpricetest', prm);
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
export async function savetest(prm) {
    try {
        var resp = await axios.post(URL + 'savetest', prm);
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
export async function searchpricetestautocomplete(prm) {
    try {
        var resp = await axios.post(URL + 'searchpriceheaderautocomplete', prm);
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
export async function copyharga(prm) {
    try {
        var resp = await axios.post(URL + 'copyharga', prm);
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
export async function validateheader(prm) {
    try {
        var resp = await axios.post(URL + 'validateheader', prm);
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



