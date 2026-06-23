const camera = document.querySelector("#camera");
const canvas = document.querySelector("#captureCanvas");
const startButton = document.querySelector("#startCamera");
const scanButton = document.querySelector("#scanButton");
const upload = document.querySelector("#imageUpload");
const status = document.querySelector("#cameraStatus");
const loading = document.querySelector("#scanLoading");
const progress = document.querySelector("#progress");
const result = document.querySelector("#arResult");
const resultIcon = document.querySelector("#arIcon");
const resultName = document.querySelector("#medicineName");
const resultMessage = document.querySelector("#safetyMessage");
const resultSources = document.querySelector("#resultSources");
const manualInput = document.querySelector("#manualMedicine");

let stream;

startButton.addEventListener("click", async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: "environment" } },
      audio: false
    });
    camera.srcObject = stream;
    scanButton.disabled = false;
    status.textContent = "Cámara activa";
    status.classList.add("online");
    startButton.textContent = "Cámara encendida";
  } catch (error) {
    status.textContent = "Sin permiso de cámara";
    alert("No fue posible abrir la cámara. Puedes elegir una fotografía.");
  }
});

scanButton.addEventListener("click", () => {
  if (!camera.videoWidth) return;
  canvas.width = camera.videoWidth;
  canvas.height = camera.videoHeight;
  canvas.getContext("2d").drawImage(camera, 0, 0);
  recognizeImage(canvas);
});

upload.addEventListener("change", () => {
  const file = upload.files[0];
  if (!file) return;

  const image = new Image();
  image.onload = () => {
    canvas.width = image.naturalWidth;
    canvas.height = image.naturalHeight;
    canvas.getContext("2d").drawImage(image, 0, 0);
    URL.revokeObjectURL(image.src);
    recognizeImage(canvas);
  };
  image.src = URL.createObjectURL(file);
});

document.querySelector("#manualTest").addEventListener("click", () => {
  analyzeText(manualInput.value);
});

async function recognizeImage(image) {
  result.hidden = true;
  loading.hidden = false;
  progress.textContent = "0%";

  try {
    const response = await Tesseract.recognize(image, "spa", {
      logger: event => {
        if (event.status === "recognizing text") {
          progress.textContent = `${Math.round(event.progress * 100)}%`;
        }
      }
    });
    await analyzeText(response.data.text);
  } catch (error) {
    showUnknown("No se pudo analizar la imagen. Intenta con más luz y acerca el nombre.");
  } finally {
    loading.hidden = true;
  }
}

async function analyzeText(text) {
  if (!text.trim()) {
    showUnknown("Escribe o captura el nombre de un medicamento.");
    return;
  }

  loading.hidden = false;
  progress.textContent = "Consultando";

  try {
    const response = await fetch("api/camera.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ text })
    });
    const data = await response.json();

    if (!response.ok || !data.identified) {
      showUnknown(data.message || "No se pudo identificar el medicamento.");
      return;
    }

    showApiResult(data);
  } catch (error) {
    showUnknown("No fue posible consultar la API del escáner.");
  } finally {
    loading.hidden = true;
  }
}

function showApiResult(data) {
  const level = data.result.level;
  result.className = `ar-result ${level}`;
  resultIcon.textContent = level === "danger" ? "✕" : level === "warning" ? "!" : "✓";
  resultName.textContent = data.medicine.name;
  resultMessage.textContent = data.result.explanation;
  resultSources.innerHTML = "";

  data.sources.forEach(source => {
    const link = document.createElement("a");
    link.href = source.url;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.textContent = source.title;
    resultSources.appendChild(link);
  });

  result.hidden = false;
}

function showUnknown(message) {
  result.className = "ar-result warning";
  resultIcon.textContent = "?";
  resultName.textContent = "No identificado";
  resultMessage.textContent = message;
  resultSources.innerHTML = "";
  result.hidden = false;
}

window.addEventListener("beforeunload", () => {
  stream?.getTracks().forEach(track => track.stop());
});
