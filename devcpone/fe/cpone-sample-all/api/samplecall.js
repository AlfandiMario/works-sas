const URL = "/one-api/mockup/sampling-all-cpone/";

export async function search(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/listpatients", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function saverequirement(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/saverequirement", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function receivesample(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/receivesample", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function search_staff(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/search_staff", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function searchcompany(token, prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/searchcompany", {
      token: token,
      search: prm,
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function search_patient(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/search_patient", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getrequirements(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getrequirements", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getstationstatus(token) {
  try {
    var resp = await axios.post(URL + "samplingcall/getstationstatus", {
      token: token,
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function save(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/save", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function newsamplingcall(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/newsamplingcall", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function xdelete(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/deletesamplingcall", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getaddress(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getaddress", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function searchcity(token, prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/searchcity", {
      token: token,
      search: prm,
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getdistrict(token, prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getdistrict", {
      id: prm.M_CityID,
      token: token,
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getkelurahan(token, prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getkelurahan", {
      token: token,
      id: prm.M_DistrictID,
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function savenewaddress(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/savenewaddress", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function saveeditaddress(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/saveeditaddress", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function deleteaddress(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/deleteaddress", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getsampletypes(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getsampletypes", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function doaction(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/doaction", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function addnewlabel(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/addnewlabel", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getdatanoterequirement(prm) {
  try {
    var resp = await axios.post(
      URL + "samplingcall/getdatanoterequirement",
      prm
    );
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function savenotesampling(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/savenotesampling", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getLocation(prm) {
  try {
    var resp = await axios.post(URL + "samplingcall/getlocation", prm);
    if (resp.status != 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }
    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}
