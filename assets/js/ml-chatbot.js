(function () {
  const boot = () => {
    const roots = Array.from(document.querySelectorAll("[data-ml-chatbot]")).filter(
      (node) => node && node.nodeType === 1
    );

    if (!roots.length || typeof mlChatbotConfig === "undefined") {
      return;
    }

    const waitForChatKit = async () => {
      if (window.customElements && window.customElements.get("openai-chatkit")) {
        return;
      }

      if (window.customElements && window.customElements.whenDefined) {
        await Promise.race([
          window.customElements.whenDefined("openai-chatkit"),
          new Promise((_, reject) => {
            window.setTimeout(() => {
              reject(
                new Error(
                  "ChatKit script did not finish loading. Check if chatkit.js is blocked by cache, CSP, or network rules."
                )
              );
            }, 8000);
          }),
        ]);
        return;
      }

      throw new Error("Browser does not support Custom Elements.");
    };

    const getFriendlyErrorMessage = (value) => {
      if (!value) {
        return mlChatbotConfig.error || "Chat unavailable.";
      }

      if (typeof value === "string") {
        return value;
      }

      if (value?.data?.message) {
        return value.data.message;
      }

      if (value?.message) {
        return value.message;
      }

      return mlChatbotConfig.error || "Chat unavailable.";
    };

    const requestClientSecret = async (action, currentClientSecret) => {
      const payload = new URLSearchParams();
      payload.set("action", action);
      payload.set("nonce", mlChatbotConfig.nonce);

      if (currentClientSecret) {
        payload.set("current_client_secret", currentClientSecret);
      }

      const response = await fetch(mlChatbotConfig.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: payload.toString(),
      });

      const rawText = await response.text();
      let data = null;

      try {
        data = rawText ? JSON.parse(rawText) : null;
      } catch (error) {}

      if (!response.ok || !data.success || !data.data?.client_secret) {
        throw new Error(
          getFriendlyErrorMessage(data) ||
            rawText ||
            mlChatbotConfig.error
        );
      }

      return data.data.client_secret;
    };

    const initializeChatKit = async (root) => {
      if (!(root instanceof Element)) {
        return;
      }

      const chatkit = root.querySelector("[data-ml-chatkit]");
      const fallback = root.querySelector("[data-ml-chatkit-fallback]");

      if (!chatkit || chatkit.dataset.initialized === "1") {
        return;
      }

      if (fallback) {
        fallback.textContent = "Loading chat...";
        fallback.hidden = false;
      }

      let loadingTimeout = null;
      let ready = false;

      if (fallback) {
        loadingTimeout = window.setTimeout(() => {
          fallback.textContent =
            "Chat loading is taking longer than expected. Check console and OpenAI domain/workflow settings.";
          fallback.hidden = false;
        }, 12000);
      }

      try {
        if (fallback) {
          fallback.textContent = "Loading chat...";
          fallback.hidden = false;
        }

        await waitForChatKit();

        const onReady = () => {
          ready = true;
          chatkit.dataset.initialized = "1";
          if (fallback) {
            fallback.hidden = true;
            fallback.textContent = "";
          }
          if (loadingTimeout) {
            window.clearTimeout(loadingTimeout);
          }
        };

        const onError = (event) => {
          const errorMessage =
            event?.detail?.error?.message ||
            mlChatbotConfig.error ||
            "Chat unavailable.";
          if (fallback) {
            fallback.textContent = errorMessage;
            fallback.hidden = false;
          }
        };

        chatkit.addEventListener("chatkit.ready", onReady);
        chatkit.addEventListener("chatkit.error", onError);

        if (fallback) {
          fallback.textContent = "Connecting chat...";
          fallback.hidden = false;
        }

        const initialClientSecret = await requestClientSecret(mlChatbotConfig.chatkit.startAction);
        let initialSecretConsumed = false;

        chatkit.setOptions({
          api: {
            async getClientSecret(currentClientSecret) {
              if (!currentClientSecret && !initialSecretConsumed) {
                initialSecretConsumed = true;
                return initialClientSecret;
              }

              if (!currentClientSecret) {
                return requestClientSecret(mlChatbotConfig.chatkit.startAction);
              }

              return requestClientSecret(
                mlChatbotConfig.chatkit.refreshAction,
                currentClientSecret
              );
            },
          },
        });
      } catch (error) {
        console.error("[Agent PopUp Helper] ChatKit init failed", error);
        if (fallback) {
          fallback.textContent =
            error?.message || mlChatbotConfig.error || "Chat unavailable.";
          fallback.hidden = false;
        }
        throw error;
      } finally {
        if (loadingTimeout && ready) {
          window.clearTimeout(loadingTimeout);
        }
      }
    };

    roots.forEach((root) => {
      if (!(root instanceof Element)) {
        return;
      }

      const panel = root.querySelector("[data-ml-chatbot-panel]");
      const toggle = root.querySelector("[data-ml-chatbot-toggle]");
      const close = root.querySelector("[data-ml-chatbot-close]");
      const isPopup = root.dataset.mlChatbotVariant === "popup";
      const popupVisibility = mlChatbotConfig.chatkit?.popupVisibility || "all";
      const popupOpenDelay = Number(mlChatbotConfig.chatkit?.popupOpenDelay || 0);

      const matchesVisibility = () => {
        if (!isPopup) {
          return true;
        }

        const isMobile = window.matchMedia("(max-width: 767px)").matches;

        if (popupVisibility === "desktop") {
          return !isMobile;
        }

        if (popupVisibility === "mobile") {
          return isMobile;
        }

        return true;
      };

      const setPopupState = async (open) => {
        if (!isPopup || !panel || !toggle) {
          return;
        }

        panel.hidden = !open;
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        root.classList.toggle("is-open", open);

        if (open) {
          try {
            await initializeChatKit(root);
          } catch (error) {
            console.error("ChatKit init failed", error);
          }
        }
      };

      if (isPopup && !matchesVisibility()) {
        root.hidden = true;
        return;
      }

      if (!isPopup) {
        initializeChatKit(root).catch((error) => {
          console.error("ChatKit init failed", error);
        });
      }

      if (toggle) {
        toggle.addEventListener("click", async () => {
          const open = toggle.getAttribute("aria-expanded") !== "true";
          await setPopupState(open);
        });
      }

      if (close) {
        close.addEventListener("click", () => {
          setPopupState(false);
        });
      }

      if (isPopup && popupOpenDelay > 0) {
        window.setTimeout(() => {
          if (matchesVisibility() && toggle && toggle.getAttribute("aria-expanded") !== "true") {
            setPopupState(true);
          }
        }, popupOpenDelay * 1000);
      }
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
    return;
  }

  boot();
})();
