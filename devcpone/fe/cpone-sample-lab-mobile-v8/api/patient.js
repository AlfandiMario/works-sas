const URL = "/one-api/mockup/sampling-lab-mobile-cpone-v8/";

// Flag object untuk memonitor status request masing-masing fungsi
let isRequestingFlags = {
    searchPatient: false,
    scanPatient: false,
    search: false,
    searchCompany: false,
    lookupStatuses: false,
    getDataBarcodes: false,
    serahkan: false,
    getStations: false,
    getLocation: false,
    skipAction: false,
    scanBarcode: false,
};

export async function search_patient(prm) {
    if (isRequestingFlags.searchPatient) {
        return { status: "ERR", message: "Request search patient in progress" };
    }

    isRequestingFlags.searchPatient = true;

    try {
        var resp = await axios.post(URL + 'patient/search_patient', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.searchPatient = false;
    }
}

export async function scan_patient(prm) {
    if (isRequestingFlags.scanPatient) {
        return { status: "ERR", message: "Request scan in progress" };
    }

    isRequestingFlags.scanPatient = true;

    try {
        var resp = await axios.post(URL + 'patient/scan_patient', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.scanPatient = false;
    }
}

export async function search(prm) {
    if (isRequestingFlags.search) {
        return { status: "ERR", message: "Request search in progress" };
    }

    isRequestingFlags.search = true;

    try {
        var resp = await axios.post(URL + 'patient/search', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.search = false;
    }
}

export async function searchcompany(token, prm) {
    if (isRequestingFlags.searchCompany) {
        return { status: "ERR", message: "Request search company in progress" };
    }

    isRequestingFlags.searchCompany = true;

    try {
        var resp = await axios.post(URL + 'patient/searchcompany', { token: token, search: prm });
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.searchCompany = false;
    }
}

export async function lookup_statuses(prm) {
    if (isRequestingFlags.lookupStatuses) {
        return { status: "ERR", message: "Request lookup_statuses in progress" };
    }

    isRequestingFlags.lookupStatuses = true;

    try {
        var resp = await axios.post(URL + 'patient/lookup_statuses', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.lookupStatuses = false;
    }
}

export async function getdatabarcodes(prm) {
    if (isRequestingFlags.getDataBarcodes) {
        return { status: "ERR", message: "Request getdatabarcodes in progress" };
    }

    isRequestingFlags.getDataBarcodes = true;

    try {
        var resp = await axios.post(URL + 'patient/lookup_barcodes', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.getDataBarcodes = false;
    }
}

export async function serahkan(prm) {
    if (isRequestingFlags.serahkan) {
        return { status: "ERR", message: "Request serahkan in progress" };
    }

    isRequestingFlags.serahkan = true;

    try {
        var resp = await axios.post(URL + 'patient/serahkan', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.serahkan = false;
    }
}

export async function getstations(token) {
    if (isRequestingFlags.getStations) {
        return { status: "ERR", message: "Request getstations in progress" };
    }

    isRequestingFlags.getStations = true;

    try {
        let prm = { token: token };
        var resp = await axios.post(URL + 'patient/getstations', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.getStations = false;
    }
}

export async function getLocation(prm) {
    if (isRequestingFlags.getLocation) {
        return { status: "ERR", message: "Request getLocation in progress" };
    }

    isRequestingFlags.getLocation = true;

    try {
        var resp = await axios.post(URL + 'patient/getlocation', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.getLocation = false;
    }
}

export async function skipaction(prm) {
    if (isRequestingFlags.skipAction) {
        return { status: "ERR", message: "Request skipaction in progress" };
    }

    isRequestingFlags.skipAction = true;

    try {
        var resp = await axios.post(URL + 'patient/skipaction', prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.skipAction = false;
    }
}

export async function scanbarcode(prm) {
    if (isRequestingFlags.scanBarcode) {
        return { status: "ERR", message: "Request scanbarcode in progress" };
    }

    isRequestingFlags.scanBarcode = true;

    try {
        var url_now = 'patient/scanbarcode';
        if (prm.station.isnonlab !== "") {
            url_now = 'patient/scanbarcode_nonlab';
        }

        var resp = await axios.post(URL + url_now, prm);
        if (resp.status != 200) {
            return { status: "ERR", message: resp.statusText };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return { status: "ERR", message: e.message };
    } finally {
        isRequestingFlags.scanBarcode = false;
    }
}
