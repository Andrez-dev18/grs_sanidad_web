document.addEventListener("DOMContentLoaded", function () {
  let currentSample = 0;
  let totalSamples = 0;
  let sampleDataCache = {};

  const today = new Date();
  const dateStr = today.toISOString().split("T")[0];
  const timeStr = today.toTimeString().split(" ")[0].substring(0, 5);

  const fechaEnvio = document.getElementById("fechaEnvio");
  const horaEnvio = document.getElementById("horaEnvio");
  const fechaToma = document.getElementById("fechaToma");

  if (fechaEnvio) fechaEnvio.value = dateStr;
  if (horaEnvio) horaEnvio.value = timeStr;
  if (fechaToma) fechaToma.value = dateStr;

  const numeroInput = document.getElementById("numeroSolicitudes");
  const container = document.getElementById("samples-container");
  const template = document.querySelector(".sample-template");

  function saveSampleDataToCache(index) {
    const sampleEl = container.querySelector(`[data-sample-index="${index}"]`);
    if (!sampleEl) return;

    sampleDataCache[index] = extractSampleData(sampleEl, index);
  }
  async function loadCodigoEnvio() {
    try {
      const res = await fetch("reserve_codigo_envio.php");
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      document.getElementById("codigoEnvio").value = data.codigo_envio;
    } catch (error) {
      console.error("Error al reservar código:", error);
      alert(
        "⚠️ No se pudo generar el código de envío. Intente recargar la página."
      );
    }
  }
  loadCodigoEnvio();
  async function cargarTiposMuestra(containerId, sampleIndex) {
    try {
      const res = await fetch("get_tipos_muestra.php");
      const tipos = await res.json();
      if (tipos.error) throw new Error(tipos.error);

      const container = document.getElementById(containerId);
      if (!container) return;

      container.innerHTML = "";
      tipos.forEach((tipo) => {
        const label = document.createElement("label");
        label.className = "radio-item";
        label.innerHTML = `
                <input type="radio" name="tipoMuestra_${sampleIndex}" value="${tipo.codigo}">
                <span>${tipo.nombre}</span>
            `;
        container.appendChild(label);
      });

      container.addEventListener("change", function (e) {
        if (e.target.matches(`input[name="tipoMuestra_${sampleIndex}"]`)) {
          const tipoId = e.target.value;

          requestAnimationFrame(() => {
            updateAnalisis(tipoId, sampleIndex);
          });
        }
      });
    } catch (error) {
      console.error("Error al cargar tipos de muestra:", error);
      alert("⚠️ No se pudieron cargar los tipos de muestra.");
    }
  }

  function createReferenceCodeBoxes(longitud, container, hiddenInput) {
    container.innerHTML = "";
    hiddenInput.value = "";

    for (let i = 0; i < longitud; i++) {
      const box = document.createElement("input");
      box.type = "text";
      box.maxLength = 1;
      box.style.cssText = `
          width: 40px;
          height: 50px; /* Aumentamos un poco la altura */
          text-align: center;
          font-size: 20px; /* Ajustamos el tamaño de fuente */
          font-weight: 600;
          border: 2px solid #cbd5e0;
          border-radius: 8px;
          background: #f7fafc;
          padding: 0; /* Eliminamos el padding interno para evitar que el texto se corte */
          line-height: 50px; /* Hacemos que la línea sea igual a la altura */
          box-sizing: border-box; /* Aseguramos que el padding y border no afecten el tamaño */
      `;
      box.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "");
        if (this.value && this.nextElementSibling)
          this.nextElementSibling.focus();
        updateHiddenValue();
      });
      box.addEventListener("keydown", function (e) {
        if (
          e.key === "Backspace" &&
          !this.value &&
          this.previousElementSibling
        ) {
          this.previousElementSibling.focus();
        }
      });
      container.appendChild(box);
    }

    function updateHiddenValue() {
      const value = Array.from(container.querySelectorAll("input"))
        .map((i) => i.value || "")
        .join("");
      hiddenInput.value = value;
    }
  }

  function clearSamples() {
    container.innerHTML = "";
    currentSample = 0;
    totalSamples = 0;
  }

  function createNavigationControls() {
    const navDiv = document.createElement("div");
    navDiv.className = "sample-navigation";
    navDiv.innerHTML = `
     <button type="button" class="nav-btn prev-btn" onclick="navigateSample(-1)" style="font-size: 18px;">
  <span>◀</span> Anterior
</button>
<div class="sample-indicator" style="font-size: 18px;">
  <span class="current-sample">1</span> de <span class="total-samples">${totalSamples}</span>
</div>
<button type="button" class="nav-btn next-btn" onclick="navigateSample(1)" style="font-size: 18px;">
  Siguiente <span>▶</span>
</button>
    `;
    return navDiv;
  }

  function createSample(index) {
    const clone = template.cloneNode(true);
    clone.style.display = "none";
    clone.classList.remove("sample-template");
    clone.classList.add("sample-item");
    clone.dataset.sampleIndex = index;

    // Actualizar IDs y nombres
    const sampleNumber = clone.querySelector(".sample-number");
    if (sampleNumber) sampleNumber.textContent = index + 1;

    // Actualizar IDs internos
    const idFields = [
      "fechaToma",
      "numeroMuestras",
      "tipoMuestraRadios",
      "codigoReferenciaContainer",
      "codigoReferenciaBoxes",
      "codigoReferenciaValue",
      "paquetesContainer",
    ];
    idFields.forEach((id) => {
      const el = clone.querySelector(`#${id}`);
      if (el) el.id = `${id}_${index}`;
    });

    // Asignar nombre a inputs si no lo tienen
    clone.querySelectorAll("input, select, textarea").forEach((input) => {
      if (input.id && !input.name) input.name = input.id;
    });

    // Cargar tipos de muestra y registrar listener
    cargarTiposMuestra(`tipoMuestraRadios_${index}`, index).then(() => {
      if (sampleDataCache[index]) {
        restoreSampleData(clone, index, sampleDataCache[index]);
        // Registrar persistencia inmediata después de que todo esté cargado
        registerImmediatePersistence(clone, index);
      } else {
        registerImmediatePersistence(clone, index);
      }
    });

    return clone;
  }

  function registerImmediatePersistence(sampleEl, index) {
    // Inputs comunes
    sampleEl.querySelectorAll("input, select, textarea").forEach((el) => {
      el.addEventListener("input", () => saveSampleDataToCache(index));
      el.addEventListener("change", () => saveSampleDataToCache(index));
    });

    // Radio buttons (tipo de muestra)
    sampleEl.addEventListener("change", (e) => {
      if (e.target.name === `tipoMuestra_${index}`) {
        saveSampleDataToCache(index);
      }
    });

    // Checkboxes de análisis y paquetes (deben estar dentro del contenedor de paquetes)
    sampleEl.addEventListener("change", (e) => {
      if (
        e.target.classList.contains("analisis-individual") ||
        e.target.classList.contains("paquete-checkbox")
      ) {
        saveSampleDataToCache(index);
      }
    });
  }
  function restoreSampleData(clone, index, data) {
    const fechaToma = clone.querySelector(`#fechaToma_${index}`);
    if (fechaToma && data.fechaToma) {
      fechaToma.value = data.fechaToma;
    }

    const numeroMuestras = clone.querySelector(`#numeroMuestras_${index}`);
    if (numeroMuestras && data.numeroMuestras) {
      numeroMuestras.value = data.numeroMuestras;
    }

    if (data.tipoMuestra) {
      const radio = clone.querySelector(
        `input[name="tipoMuestra_${index}"][value="${data.tipoMuestra}"]`
      );
      if (radio) {
        radio.checked = true;

        updateAnalisis(data.tipoMuestra, index);

        setTimeout(() => {
          if (data.codigoReferenciaValue) {
            const refInput = clone.querySelector(
              `#codigoReferenciaValue_${index}`
            );
            if (refInput) {
              refInput.value = data.codigoReferenciaValue;
              const boxes = clone.querySelectorAll(
                `#codigoReferenciaBoxes_${index} input`
              );
              const digits = data.codigoReferenciaValue.split("");
              boxes.forEach((box, i) => {
                box.value = digits[i] || "";
              });
            }
          }

          if (data.analisisSeleccionados) {
            data.analisisSeleccionados.forEach((codigo) => {
              const cb = clone.querySelector(
                `.analisis-individual[value="${codigo}"]`
              );
              if (cb) cb.checked = true;
            });
          }

          if (data.paquetesSeleccionados) {
            data.paquetesSeleccionados.forEach((codigo) => {
              const cb = clone.querySelector(
                `.paquete-checkbox[data-paquete-id="${codigo}"]`
              );
              if (cb) cb.checked = true;
            });
          }
        }, 150); // Delay para asegurar que updateAnalisis haya terminado
      }
    }

    const observaciones = clone.querySelector("textarea");
    if (observaciones && data.observaciones) {
      observaciones.value = data.observaciones;
    }
  }
  window.updateAnalisis = async function (tipoId, sampleIndex) {
    function waitForElement(selector, timeout = 2000) {
      return new Promise((resolve, reject) => {
        const startTime = Date.now();
        const check = () => {
          const el = document.querySelector(selector);
          if (el) {
            resolve(el);
          } else if (Date.now() - startTime > timeout) {
            reject(new Error(`Timeout: ${selector} not found`));
          } else {
            setTimeout(check, 50);
          }
        };
        check();
      });
    }

    try {
      ///const analisisContainer = await waitForElement(
      /// `#analisisContainer_${sampleIndex}`
      ///);
      const codigoRefContainer = await waitForElement(
        `#codigoReferenciaContainer_${sampleIndex}`
      );
      ///const analisisContent = await waitForElement(
      ///`#analisisContent_${sampleIndex}`
      ///);
      const codigoRefBoxes = await waitForElement(
        `#codigoReferenciaBoxes_${sampleIndex}`
      );
      const codigoRefValue = await waitForElement(
        `#codigoReferenciaValue_${sampleIndex}`
      );
      const paquetesContainer = await waitForElement(
        `#paquetesContainer_${sampleIndex}`
      );

      if (!tipoId) {
        /// analisisContainer.style.display = "none";
        codigoRefContainer.style.display = "none";
        paquetesContainer.style.display = "none";
        return;
      }

      const res = await fetch(`get_config_muestra.php?tipo=${tipoId}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      codigoRefContainer.style.display = "block";
      createReferenceCodeBoxes(
        data.tipo_muestra.longitud_codigo,
        codigoRefBoxes,
        codigoRefValue
      );

      const analisisPorPaquete = {};
      const analisisSinPaquete = [];

      data.analisis.forEach((a) => {
        if (a.paquete !== null) {
          if (!analisisPorPaquete[a.paquete])
            analisisPorPaquete[a.paquete] = [];
          analisisPorPaquete[a.paquete].push(a);
        } else {
          analisisSinPaquete.push(a);
        }
      });

      const analisisMapParaCheckboxes = {};
      data.analisis.forEach((a) => {
        if (a.paquete !== null) {
          if (!analisisMapParaCheckboxes[a.paquete])
            analisisMapParaCheckboxes[a.paquete] = [];
          analisisMapParaCheckboxes[a.paquete].push(a.codigo);
        }
      });
      paquetesContainer.dataset.analisisMap = JSON.stringify(
        analisisMapParaCheckboxes
      );

      // Generar HTML por paquete + sus análisis
      let contenidoCompleto = "";

      // Mostrar cada paquete con sus análisis
      data.paquetes.forEach((p) => {
        const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];

        // Bloque del paquete (igual que antes)
        let bloque = `
    <div class="paquete-con-analisis" style="margin-bottom: 24px;">
      <label class="paquete-item" style="display: flex; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 8px 12px; cursor: pointer; margin-bottom: 8px;">
        <input type="checkbox" class="paquete-checkbox" data-paquete-id="${p.codigo}" style="margin-right: 8px;">
        <span>${p.nombre}</span>
      </label>
  `;

        // Si tiene análisis, los mostramos debajo
        if (analisisDelPaquete.length > 0) {
          bloque += `
      <div class="checkbox-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 8px; margin-left: 20px;">
        ${analisisDelPaquete
          .map(
            (a) => `
            <label class="checkbox-item" style="display: flex; align-items: center; padding: 10px; background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 6px; cursor: pointer;">
              <input type="checkbox" name="analisis_${sampleIndex}[]" class="analisis-individual" value="${a.codigo}" data-nombre="${a.nombre}" style="margin-right: 10px; width: 16px; height: 16px;">
              <span style="font-size: 14px;">${a.nombre}</span>
            </label>
          `
          )
          .join("")}
      </div>
    `;
        }

        bloque += `</div>`;
        contenidoCompleto += bloque;
      });

      // Finalmente, agregar análisis sueltos (sin paquete) al final, si existen
      if (analisisSinPaquete.length > 0) {
        contenidoCompleto += `
    <div class="analisis-sin-paquete" style="margin-top: 24px;">
      <h4 style="margin-bottom: 12px; font-size: 14px; color: #475569;">Otros análisis</h4>
      <div class="checkbox-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
        ${analisisSinPaquete
          .map(
            (a) => `
            <label class="checkbox-item" style="display: flex; align-items: center; padding: 10px; background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 6px; cursor: pointer;">
              <input type="checkbox" name="analisis_${sampleIndex}[]" class="analisis-individual" value="${a.codigo}" data-nombre="${a.nombre}" style="margin-right: 10px; width: 16px; height: 16px;">
              <span style="font-size: 14px;">${a.nombre}</span>
            </label>
          `
          )
          .join("")}
      </div>
    </div>
  `;
      }

      paquetesContainer.innerHTML = contenidoCompleto || "";
      paquetesContainer.style.display = contenidoCompleto ? "block" : "none";

      ///analisisContainer.style.display = "none";
      paquetesContainer.querySelectorAll(".paquete-checkbox").forEach((cb) => {
        cb.addEventListener("change", function () {
          const paqueteId = parseInt(this.dataset.paqueteId);
          const isChecked = this.checked;
          const map = JSON.parse(paquetesContainer.dataset.analisisMap || "{}");
          const analisisIds = map[paqueteId] || [];

          analisisIds.forEach((id) => {
            // 🔥 Aquí está el cambio: buscar en paquetesContainer (donde ahora están los checkboxes)
            const analisisCheckbox = paquetesContainer.querySelector(
              `.analisis-individual[value="${id}"]`
            );
            if (analisisCheckbox) {
              if (isChecked) {
                analisisCheckbox.checked = true;
              } else {
                let stillActive = false;
                Object.keys(map).forEach((pId) => {
                  if (parseInt(pId) !== paqueteId && map[pId].includes(id)) {
                    const otroPaquete = paquetesContainer.querySelector(
                      `.paquete-checkbox[data-paquete-id="${pId}"]`
                    );
                    if (otroPaquete && otroPaquete.checked) {
                      stillActive = true;
                    }
                  }
                });
                if (!stillActive) {
                  analisisCheckbox.checked = false;
                }
              }
            }
          });
        });
      });
    } catch (error) {
      console.error("Error al cargar análisis:", error);
      alert("⚠️ No se pudieron cargar los análisis. Intente nuevamente.");

      const containers = [
        /// `#analisisContainer_${sampleIndex}`,
        `#codigoReferenciaContainer_${sampleIndex}`,
        `#paquetesContainer_${sampleIndex}`,
      ];
      containers.forEach((id) => {
        const el = document.querySelector(id);
        if (el) el.style.display = "none";
      });
    }
  };

  window.navigateSample = function (direction) {
    const newIndex = currentSample + direction;

    if (newIndex < 0 || newIndex >= totalSamples) return;

    const currentElement = container.querySelector(
      `[data-sample-index="${currentSample}"]`
    );
    if (currentElement) {
      currentElement.style.display = "none";
      currentElement.classList.remove("active");
    }

    currentSample = newIndex;
    const newElement = container.querySelector(
      `[data-sample-index="${currentSample}"]`
    );
    if (newElement) {
      newElement.style.display = "block";
      newElement.classList.add("active");

      const navigation = container.querySelector(".sample-navigation");
      if (navigation) {
        navigation.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }

    const indicator = document.querySelector(".current-sample");
    if (indicator) indicator.textContent = currentSample + 1;

    updateNavigationButtons();
  };

  function updateNavigationButtons() {
    const prevBtn = document.querySelector(".prev-btn");
    const nextBtn = document.querySelector(".next-btn");

    if (prevBtn) prevBtn.disabled = currentSample === 0;
    if (nextBtn) nextBtn.disabled = currentSample === totalSamples - 1;
  }

  numeroInput.addEventListener("input", function () {
    let count = parseInt(this.value, 10);
    if (isNaN(count) || count < 1) {
      clearSamples();
      return;
    }
    const max = 20;
    if (count > max) {
      count = max;
      this.value = count;
    }

    // 1. Guardar estado actual ANTES de borrar
    for (let i = 0; i < totalSamples; i++) {
      saveSampleDataToCache(i);
    }

    // 2. Reconstruir
    clearSamples();
    totalSamples = count;

    // 3. Crear controles de navegación si es necesario
    if (count > 1) {
      container.appendChild(createNavigationControls());
    }

    // 4. Crear todas las muestras (restaurar desde caché si existe)
    for (let i = 0; i < count; i++) {
      const sampleEl = createSample(i);
      container.appendChild(sampleEl);
    }

    // 5. Mostrar la primera muestra
    if (count > 0) {
      showSample(0);
    }
  });
  function showSample(index) {
    // Ocultar actual
    const currentEl = container.querySelector(
      `[data-sample-index="${currentSample}"]`
    );
    if (currentEl) {
      currentEl.style.display = "none";
      currentEl.classList.remove("active");
    }

    // Mostrar nueva
    currentSample = index;
    const newEl = container.querySelector(
      `[data-sample-index="${currentSample}"]`
    );
    if (newEl) {
      newEl.style.display = "block";
      newEl.classList.add("active");
    }

    // Actualizar UI
    const indicator = document.querySelector(".current-sample");
    if (indicator) {
      indicator.textContent = index + 1;
    }
    updateNavigationButtons();
  }

  if (numeroInput.value) {
    numeroInput.dispatchEvent(new Event("input"));
  }

  function extractSampleData(sampleEl, index) {
    const data = {};

    const fechaToma = sampleEl.querySelector(`#fechaToma_${index}`);
    if (fechaToma) data.fechaToma = fechaToma.value;
    const numMuestrasInput = sampleEl.querySelector(`#numeroMuestras_${index}`);
    if (numMuestrasInput) data.numeroMuestras = numMuestrasInput.value;

    const tipoMuestra = sampleEl.querySelector(
      `input[name="tipoMuestra_${index}"]:checked`
    );
    if (tipoMuestra) data.tipoMuestra = tipoMuestra.value;

    const codigoRef = sampleEl.querySelector(`#codigoReferenciaValue_${index}`);
    if (codigoRef) data.codigoReferenciaValue = codigoRef.value;

    const analisisChecks = sampleEl.querySelectorAll(
      `.analisis-individual:checked`
    );
    data.analisisSeleccionados = Array.from(analisisChecks).map(
      (cb) => cb.value
    );

    const paqueteChecks = sampleEl.querySelectorAll(
      `.paquete-checkbox:checked`
    );
    data.paquetesSeleccionados = Array.from(paqueteChecks).map(
      (cb) => cb.dataset.paqueteId
    );

    const observaciones = sampleEl.querySelector("textarea");
    if (observaciones) data.observaciones = observaciones.value;

    return data;
  }

  function logout() {
    /*document.getElementById('dashboard').classList.remove('active');
            document.getElementById('loginScreen').style.display = 'flex';*/
    if (confirm("¿Desea cerrar la sesión?")) {
      window.location.href = "logout.php";
    }
  }

  window.handleSampleSubmit = function (event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById("sampleForm"));

    generateSummary(formData);

    document.getElementById("confirmModal").style.display = "flex";
  };

  function generateSummary(formData) {
    const numeroSolicitudes = parseInt(formData.get("numeroSolicitudes"));
    const fechaEnvio = formData.get("fechaEnvio");
    const horaEnvio = formData.get("horaEnvio");
    const laboratorioCodigo = formData.get("laboratorio");
    const empresaTransporte = formData.get("empresa_transporte");
    const autorizadoPor = formData.get("autorizado_por");
    const usuarioRegistrador = formData.get("usuario_registrador") || "user"; // Valor por defecto
    const usuarioResponsable = formData.get("usuario_responsable");

    let laboratorioNombre = "No disponible";
    const laboratorioSelect = document.getElementById("laboratorio");
    if (laboratorioSelect) {
      const selectedOption =
        laboratorioSelect.options[laboratorioSelect.selectedIndex];
      laboratorioNombre = selectedOption
        ? selectedOption.text
        : "No seleccionado";
    }

    let summaryHTML = `
        <h3>📋 Resumen del Envío</h3>
        <br>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
  <!-- Grupo 1 (envío) -->
  <div style="display: flex; flex-direction: column; gap: 8px;">
    <div><strong>Código de Envío:</strong> <span id="resumenCodigoEnvio"></span></div>
    <div><strong>Laboratorio:</strong> ${laboratorioNombre}</div>
    <div><strong>Fecha de Envío:</strong> ${fechaEnvio}</div>
    <div><strong>Hora de Envío:</strong> ${horaEnvio}</div>
  </div>

  <!-- Grupo 2 (responsables) -->
  <div style="display: flex; flex-direction: column; gap: 8px;">
    <div><strong>Autorizado por:</strong> ${autorizadoPor}</div>
    <div><strong>Usuario Registrador:</strong> ${usuarioRegistrador}</div>
    <div><strong>Usuario Responsable:</strong> ${usuarioResponsable}</div>
    <div><strong>Número de Muestras:</strong> ${numeroSolicitudes}</div>
  </div>
</div>
        <h3>🧪 Solicitudes</h3>
    `;

    for (let i = 0; i < numeroSolicitudes; i++) {
      const sampleEl = document.querySelector(
        `.sample-item[data-sample-index="${i}"]`
      );
      if (!sampleEl) continue;

      const tipoMuestraRadio = sampleEl.querySelector(
        `input[name="tipoMuestra_${i}"]:checked`
      );
      const tipoMuestraNombre = tipoMuestraRadio
        ? tipoMuestraRadio.nextElementSibling.textContent
        : "No seleccionado";

      const fechaTomaInput = sampleEl.querySelector(`#fechaToma_${i}`);
      const fechaToma = fechaTomaInput ? fechaTomaInput.value : "-";
      const numeroMuestras = formData.get(`numeroMuestras_${i}`) || "1";

      const codigoRefBoxes = sampleEl.querySelectorAll(
        `#codigoReferenciaBoxes_${i} input`
      );
      const codigoRef = Array.from(codigoRefBoxes)
        .map((box) => box.value || "")
        .join("");

      const observacionesTextarea = sampleEl.querySelector("textarea");
      const observaciones = observacionesTextarea
        ? observacionesTextarea.value
        : "Ninguna";

      const analisisSeleccionados = [];
      const analisisCheckboxes = sampleEl.querySelectorAll(
        ".analisis-individual:checked"
      );
      analisisCheckboxes.forEach((cb) => {
        analisisSeleccionados.push(cb.nextElementSibling.textContent);
      });

      summaryHTML += `
            <div style="border: 1px solid #e2e8f0; padding: 16px; margin: 12px 0; border-radius: 8px; background: #f8fafc;">
                <h4 style="margin: 0 0 12px 0; color: #2d3748;">Solicitud #${
                  i + 1
                }</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 14px;">
                    <div><strong>Tipo de Muestra:</strong> ${tipoMuestraNombre}</div>
                    <div><strong>Fecha de Toma:</strong> ${fechaToma}</div>
                    <div><strong>N° de Muestras:</strong> ${numeroMuestras}</div>
                    <div><strong>Código de Referencia:</strong> ${codigoRef}</div>
                    <div><strong>Observaciones:</strong> ${observaciones}</div>
                </div>
                <div style="margin-top: 12px;">
                    <strong>Análisis Solicitados:</strong><br>
                    <span style="display: inline-block; margin-top: 4px; padding: 6px 10px; background: #edf2f7; border-radius: 6px; font-size: 13px;">
                        ${
                          analisisSeleccionados.length > 0
                            ? analisisSeleccionados.join(", ")
                            : "Ninguno"
                        }
                    </span>
                </div>
            </div>
        `;
    }

    document.getElementById("summaryContent").innerHTML = summaryHTML;

    document.getElementById("resumenCodigoEnvio").textContent =
      document.getElementById("codigoEnvio").value;
  }
  window.confirmSubmit = async function () {
    const formData = new FormData(document.getElementById("sampleForm"));
    for (const [key, value] of formData.entries()) {
      console.log(key, ":", value);
    }
    try {
      const response = await fetch("guardar_muestra.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.status === "success") {
        alert("✅ " + result.message + ". Código: " + result.codigoEnvio);

        document.getElementById("confirmModal").style.display = "none";
        document.getElementById("sampleForm").reset();
        const input = document.getElementById("numeroSolicitudes");
        input.value = "";
        input.dispatchEvent(new Event("input"));

        // Regenerar código de envío
        loadCodigoEnvio();
      } else {
        throw new Error(result.error || "Error desconocido al guardar.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("❌ Error al guardar el registro: " + error.message);
    }
  };

  window.closeConfirmModal = function () {
    document.getElementById("confirmModal").style.display = "none";
  };
});
