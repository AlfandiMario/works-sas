const URL = "/one-api/mockup/purchase/requester/";

export async function search(payload) {
  try {
    var response = await axios.post(URL + "purchaseRequestPo/search", payload);
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function searchDetail(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/searchDetail",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getRegional(payload) {
  try {
    var response = await axios.post(URL + "purchaseRequestPo/getRegional", payload);
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getBranch(payload) {
  try {
    var response = await axios.post(URL + "purchaseRequestPo/getBranch", payload);
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function saveRequest(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/saveRequest",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function updateRequest(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/updateRequest",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function deleteRequest(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/deleteRequest",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function saveDetail(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/saveDetail",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}
export async function updateDetail(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/updateDetail",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}
export async function deleteDetail(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/deleteDetail",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function orderRequest(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/orderRequest",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getVendor(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/getVendor",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getVendorForm(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/getVendorForm",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getItemType(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/getItemType",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getItemTypeForm(payload) {
  try {
    var response = await axios.post(
      URL + "purchaseRequestPo/getItemTypeForm",
      payload
    );
    if (response.status !== 200) {
      return {
        status: "ERR",
        message: response.statusText,
      };
    }

    let data = response.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}
