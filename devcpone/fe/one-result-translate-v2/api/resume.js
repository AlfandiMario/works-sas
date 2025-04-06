const URL = "/one-api/mockup/result-translate/resulttranslate/";

// https://devcpone.aplikasi.web.id/one-api/mockup/mdprice/mdprice/searchpriceheader/

export async function getsetup(prm) {
    try {
        var resp = await axios.post(URL + 'getsetup', prm);
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
export async function search(prm) {
    try {
        var resp = await axios.post(URL + 'search', prm);
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
export async function getdetail(prm) {
    try {
        var resp = await axios.post(URL + 'getdetail', prm);
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
export async function getdoctor(prm) {
    try {
        var resp = await axios.post(URL + 'getdoctor', prm);
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
export async function getFitnessCategory(prm) {
    try {
        var resp = await axios.post(URL + 'getFitnessCategory', prm);
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
export async function generateFitnessCategory(prm) {
    try {
        var resp = await axios.post(URL + 'generateFitnessCategory', prm);
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
export async function save(prm) {
    try {
        var resp = await axios.post(URL + 'save', prm);
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
export async function saveNonlab(prm) {
    try {
        var resp = await axios.post(URL + 'saveNonlab', prm);
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
export async function saveFisikUmum(prm) {
    try {
        var resp = await axios.post(URL + 'saveFisikUmum', prm);
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
export async function savedoctor(prm) {
    try {
        var resp = await axios.post(URL + 'savedoctor', prm);
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