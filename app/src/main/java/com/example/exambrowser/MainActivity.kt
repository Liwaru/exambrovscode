package com.example.exambrowser

import android.os.Bundle
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.view.KeyEvent
import android.view.View
import android.view.ViewGroup
import android.view.WindowInsets
import android.view.WindowInsetsController
import android.webkit.WebView
import android.webkit.WebViewClient
import android.webkit.JavascriptInterface
import android.widget.FrameLayout
import android.widget.EditText
import android.widget.TextView
import androidx.activity.OnBackPressedCallback
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.google.android.material.button.MaterialButton
import com.google.android.material.snackbar.Snackbar
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.util.UUID

class MainActivity : AppCompatActivity() {
    companion object {
        private const val BASE_URL = "http://10.46.96.185:8002"
        private const val EXAM_URL = "http://elsph.permataharapanku.sch.id"
        private const val PREFS_NAME = "exam_browser_state"
        private const val KEY_ACTIVE_SESSION_ID = "active_session_id"
        private const val KEY_DEVICE_ID = "device_id"
        private const val KEY_STUDENT_NAME = "student_name"
    }

    private var activeSessionId: Long? = null
    private var examWebView: WebView? = null
    private var isForceExiting = false
    private val heartbeatHandler = Handler(Looper.getMainLooper())
    private val heartbeatRunnable = object : Runnable {
        override fun run() {
            activeSessionId?.let { sessionId ->
                Thread {
                    postSessionEvent(
                        sessionId = sessionId,
                        endpoint = "heartbeat",
                        eventType = "heartbeat",
                        message = "Aplikasi siswa masih aktif."
                    )
                }.start()
                heartbeatHandler.postDelayed(this, 30_000)
            }
        }
    }
    private val backPressedCallback = object : OnBackPressedCallback(true) {
        override fun handleOnBackPressed() {
            if (examWebView != null) {
                reportStudentAction("back_pressed", "Siswa menekan tombol Back saat ujian berlangsung.")
                showExitPinDialog()
            } else {
                isEnabled = false
                onBackPressedDispatcher.onBackPressed()
                isEnabled = true
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        onBackPressedDispatcher.addCallback(this, backPressedCallback)

        val savedSessionId = getPrefs().getLong(KEY_ACTIVE_SESSION_ID, -1L).takeIf { it > 0 }
        if (savedSessionId != null) {
            activeSessionId = savedSessionId
            Thread {
                postSessionEvent(
                    sessionId = savedSessionId,
                    endpoint = "activity",
                    eventType = "app_reopened",
                    message = "Siswa membuka aplikasi lagi setelah HP restart atau aplikasi ditutup paksa saat sesi masih aktif."
                )
            }.start()
            showExamWebsite()
        } else {
            showPinInput()
        }
    }

    private fun showPinInput() {
        setContentView(R.layout.activity_pin)
        applySystemBarsPadding(R.id.pinMain)

        val studentNameLayout = findViewById<TextInputLayout>(R.id.studentNameLayout)
        val studentNameInput = findViewById<TextInputEditText>(R.id.studentNameInput)
        val pinLayout = findViewById<TextInputLayout>(R.id.pinLayout)
        val pinInput = findViewById<TextInputEditText>(R.id.pinInput)
        val pinButton = findViewById<MaterialButton>(R.id.pinButton)

        studentNameInput.setText(getSavedStudentName())

        pinButton.setOnClickListener {
            val studentName = studentNameInput.text?.toString()
                ?.replace(Regex("\\s+"), " ")
                ?.trim()
                .orEmpty()
            val pin = pinInput.text?.toString().orEmpty()
            if (studentName.length < 3) {
                studentNameLayout.error = "Nama siswa wajib diisi minimal 3 huruf."
                return@setOnClickListener
            }

            if (pin.length != 6) {
                studentNameLayout.error = null
                pinLayout.error = "PIN harus 6 digit."
                return@setOnClickListener
            }

            studentNameLayout.error = null
            pinLayout.error = null
            pinButton.isEnabled = false

            Thread {
                val result = joinExamSession(pin, studentName)

                runOnUiThread {
                    pinButton.isEnabled = true

                    if (result.success) {
                        Snackbar.make(
                            findViewById(R.id.pinMain),
                            "PIN benar.",
                            Snackbar.LENGTH_SHORT
                        ).show()
                        activeSessionId = result.sessionId
                        saveDetectedStudentName(studentName)
                        result.sessionId?.let { saveActiveSession(it) }
                        pinButton.postDelayed({ showExamWebsite() }, 700)
                    } else {
                        pinLayout.error = result.message
                    }
                }
            }.start()
        }
    }

    private fun joinExamSession(pin: String, studentName: String): JoinResult {
        return try {
            val connection = (URL("$BASE_URL/api/student/exam-sessions/join").openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = 10_000
                readTimeout = 10_000
                doOutput = true
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Accept", "application/json")
            }

            connection.outputStream.use { output ->
                output.write(
                    JSONObject()
                        .put("pin", pin)
                        .put("student_username", studentName)
                        .put("device_id", getStoredDeviceId())
                        .put("device_name", getDeviceName())
                        .toString()
                        .toByteArray()
                )
            }

            val responseCode = connection.responseCode
            val responseBody = (if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream
            })?.bufferedReader()?.use { it.readText() }.orEmpty()

            val json = JSONObject(responseBody)
            if (responseCode in 200..299) {
                JoinResult(
                    success = true,
                    message = json.optString("message", "Berhasil masuk ujian."),
                    sessionTitle = json.optJSONObject("session")?.optString("title", "ujian").orEmpty(),
                    sessionId = json.optJSONObject("session")?.optLong("id")
                        ?.takeIf { it > 0 }
                )
            } else {
                JoinResult(
                    success = false,
                    message = json.optString("message", "PIN tidak valid."),
                    sessionTitle = "",
                    sessionId = null
                )
            }
        } catch (_: Exception) {
            JoinResult(
                success = false,
                message = "Tidak bisa terhubung ke server.",
                sessionTitle = "",
                sessionId = null
            )
        }
    }

    private data class JoinResult(
        val success: Boolean,
        val message: String,
        val sessionTitle: String,
        val sessionId: Long?
    )

    private fun showExamWebsite() {
        setContentView(R.layout.activity_exam_web)
        applySystemBarsPadding(R.id.examWebMain)

        examWebView = findViewById<WebView>(R.id.examWebView).apply {
            webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView?, url: String?): Boolean {
                    if (url.isNullOrBlank()) return false

                    val currentHost = URL(EXAM_URL).host
                    val nextHost = runCatching { URL(url).host }.getOrNull()
                    val nextPath = runCatching { URL(url).path.lowercase() }.getOrDefault("")
                    val isExitLikeNavigation =
                        "logout" in nextPath ||
                            "keluar" in nextPath ||
                            "signout" in nextPath ||
                            "exit" in nextPath

                    return if (nextHost == currentHost && !isExitLikeNavigation) {
                        view?.loadUrl(url)
                        true
                    } else {
                        showExitPinDialog()
                        true
                    }
                }

                override fun onPageFinished(view: WebView?, url: String?) {
                    super.onPageFinished(view, url)
                    activeSessionId?.let { sessionId ->
                        Thread {
                            postSessionEvent(
                                sessionId = sessionId,
                                endpoint = "activity",
                                eventType = "elearning_page_loaded",
                                message = "Halaman e-learning terbuka: ${url.orEmpty()}"
                            )
                        }.start()
                    }
                    injectStudentIdentityDetector()
                }
            }
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            addJavascriptInterface(StudentIdentityBridge(), "ExamBrowserBridge")
            loadUrl(EXAM_URL)
        }

        enterExamLockMode()
        startHeartbeat()

        findViewById<TextView>(R.id.exitExamButton).setOnClickListener {
            reportStudentAction("exit_button_pressed", "Siswa menekan tombol keluar ujian di aplikasi.")
            Snackbar.make(
                findViewById(R.id.examWebMain),
                "Membuka PIN keluar...",
                Snackbar.LENGTH_SHORT
            ).show()
            showExitPinDialog()
        }
    }

    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_BACK && examWebView != null) {
            reportStudentAction("back_pressed", "Siswa menekan tombol Back saat ujian berlangsung.")
            showExitPinDialog()
            return true
        }

        return super.onKeyDown(keyCode, event)
    }

    private fun showExitPinDialog() {
        val exitPinInput = EditText(this).apply {
            hint = "PIN keluar"
            inputType = android.text.InputType.TYPE_CLASS_NUMBER
            setSingleLine(true)
            maxLines = 1
        }

        val container = FrameLayout(this).apply {
            val padding = (24 * resources.displayMetrics.density).toInt()
            setPadding(padding, 0, padding, 0)
            addView(
                exitPinInput,
                ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.WRAP_CONTENT
                )
            )
        }

        val dialog = AlertDialog.Builder(this)
            .setTitle("PIN Keluar")
            .setView(container)
            .setNegativeButton("Batal", null)
            .setPositiveButton("Keluar", null)
            .create()

        dialog.setOnShowListener {
            dialog.getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
                val enteredPin = exitPinInput.text?.toString().orEmpty()
                val sessionId = activeSessionId
                if (sessionId == null) {
                    exitPinInput.error = "Sesi ujian belum terbaca."
                    return@setOnClickListener
                }

                dialog.getButton(AlertDialog.BUTTON_POSITIVE).isEnabled = false

                Thread {
                    val result = verifyExitPin(sessionId, enteredPin)

                    runOnUiThread {
                        dialog.getButton(AlertDialog.BUTTON_POSITIVE).isEnabled = true

                        if (!result.success) {
                            exitPinInput.error = result.message
                            return@runOnUiThread
                        }

                        dialog.dismiss()
                        exitExamLockMode()
                        clearActiveSession()
                        stopHeartbeat()
                        examWebView?.destroy()
                        examWebView = null
                        activeSessionId = null
                        finishAndRemoveTask()
                    }
                }.start()
            }
        }

        dialog.show()
    }

    private fun getPrefs() = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)

    private fun getStoredDeviceId(): String {
        val prefs = getPrefs()
        val existing = prefs.getString(KEY_DEVICE_ID, null)
        if (!existing.isNullOrBlank()) {
            return existing
        }

        val generated = UUID.randomUUID().toString()
        prefs.edit().putString(KEY_DEVICE_ID, generated).apply()
        return generated
    }

    private fun getDeviceName(): String {
        return listOf(Build.MANUFACTURER, Build.MODEL)
            .filter { it.isNotBlank() }
            .joinToString(" ")
    }

    private fun getSavedStudentName(): String {
        return getPrefs().getString(KEY_STUDENT_NAME, null).orEmpty()
    }

    private fun saveActiveSession(sessionId: Long) {
        getPrefs().edit()
            .putLong(KEY_ACTIVE_SESSION_ID, sessionId)
            .apply()
    }

    private fun saveDetectedStudentName(studentName: String) {
        getPrefs().edit()
            .putString(KEY_STUDENT_NAME, studentName)
            .apply()
    }

    private fun injectStudentIdentityDetector() {
        examWebView?.evaluateJavascript(
            """
            (function () {
                if (window.__examBrowserIdentityDetectorInstalled) return;
                window.__examBrowserIdentityDetectorInstalled = true;
                window.__examBrowserLastIdentity = '';

                function clean(value) {
                    return String(value || '')
                        .replace(/\s+/g, ' ')
                        .replace(/^(nama lengkap|nama siswa|nama|name|siswa|student|user|username|akun|profil|profile|login sebagai|masuk sebagai)\s*[:\-]\s*/i, '')
                        .trim();
                }

                function isLikelyName(value) {
                    var text = clean(value);
                    if (text.length < 3 || text.length > 80) return false;
                    if (/[{}<>=]/.test(text)) return false;
                    if (/\b(login|logout|keluar|masuk|dashboard|ujian|exam|home|menu|profil|profile|password|kelas|mapel|nilai|absensi|materi|tugas)\b/i.test(text)) return false;
                    return /[A-Za-z]/.test(text);
                }

                function pickFromText(text) {
                    var normalized = String(text || '').replace(/\s+/g, ' ').trim();
                    var patterns = [
                        /(?:nama lengkap|nama siswa|nama|login sebagai|masuk sebagai)\s*[:\-]\s*([A-Za-zÀ-ÿ.'` ]{3,80})/i,
                        /(?:username|user)\s*[:\-]\s*([A-Za-z0-9_.\- ]{3,80})/i
                    ];

                    for (var i = 0; i < patterns.length; i++) {
                        var match = normalized.match(patterns[i]);
                        if (match && isLikelyName(match[1])) return clean(match[1]);
                    }

                    return '';
                }

                function collectCandidate(node) {
                    if (!node) return '';

                    var attrs = [
                        'data-user-name',
                        'data-username',
                        'data-name',
                        'data-nama',
                        'aria-label',
                        'title',
                        'alt'
                    ];

                    for (var i = 0; i < attrs.length; i++) {
                        var attrValue = node.getAttribute && node.getAttribute(attrs[i]);
                        if (isLikelyName(attrValue)) return clean(attrValue);
                    }

                    var text = node.innerText || node.textContent || '';
                    var fromPattern = pickFromText(text);
                    if (fromPattern) return fromPattern;

                    var direct = clean(text);
                    if (isLikelyName(direct)) return direct;

                    return '';
                }

                function findName() {
                    var selectors = [
                        '[data-user-name]',
                        '[data-username]',
                        '[data-name]',
                        '[data-nama]',
                        '[class*="user"]',
                        '[id*="user"]',
                        '[class*="siswa"]',
                        '[id*="siswa"]',
                        '[class*="nama"]',
                        '[id*="nama"]',
                        '[class*="name"]',
                        '[id*="name"]',
                        '[class*="profile"]',
                        '[id*="profile"]',
                        '[class*="akun"]',
                        '[id*="akun"]',
                        '.dropdown-toggle',
                        '.dropdown-menu',
                        '.nav-link',
                        '.user-panel',
                        '.user-info',
                        '.profile-info',
                        '.navbar',
                        '.topbar',
                        '.header',
                        'header'
                    ];

                    for (var i = 0; i < selectors.length; i++) {
                        var nodes = document.querySelectorAll(selectors[i]);
                        for (var j = 0; j < nodes.length; j++) {
                            var value = collectCandidate(nodes[j]);
                            if (value) return value;
                        }
                    }

                    var bodyPattern = pickFromText(document.body && document.body.innerText);
                    if (bodyPattern) return bodyPattern;

                    return '';
                }

                function report() {
                    var name = findName();
                    if (name && name !== window.__examBrowserLastIdentity && window.ExamBrowserBridge) {
                        window.__examBrowserLastIdentity = name;
                        window.ExamBrowserBridge.onStudentNameDetected(name);
                    }
                }

                report();
                setTimeout(report, 1000);
                setTimeout(report, 3000);
                setTimeout(report, 7000);
                setInterval(report, 5000);

                if (window.MutationObserver && document.body) {
                    var observer = new MutationObserver(function () {
                        clearTimeout(window.__examBrowserIdentityTimer);
                        window.__examBrowserIdentityTimer = setTimeout(report, 500);
                    });
                    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
                }
            })();
            """.trimIndent(),
            null
        )
    }

    inner class StudentIdentityBridge {
        @JavascriptInterface
        fun onStudentNameDetected(rawName: String?) {
            val studentName = rawName
                ?.replace(Regex("\\s+"), " ")
                ?.trim()
                ?.takeIf { it.length in 3..80 }
                ?: return

            if (studentName == getSavedStudentName()) {
                return
            }

            saveDetectedStudentName(studentName)
            activeSessionId?.let { sessionId ->
                Thread {
                    postSessionEvent(
                        sessionId = sessionId,
                        endpoint = "activity",
                        eventType = "student_identified",
                        message = "Identitas siswa terbaca dari halaman e-learning."
                    )
                }.start()
            }
        }
    }

    private fun clearActiveSession() {
        getPrefs().edit()
            .remove(KEY_ACTIVE_SESSION_ID)
            .remove(KEY_STUDENT_NAME)
            .apply()
    }

    private fun startHeartbeat() {
        heartbeatHandler.removeCallbacks(heartbeatRunnable)
        heartbeatHandler.post(heartbeatRunnable)
    }

    private fun stopHeartbeat() {
        heartbeatHandler.removeCallbacks(heartbeatRunnable)
    }

    private fun postSessionEvent(sessionId: Long, endpoint: String, eventType: String, message: String) {
        runCatching {
            val connection = (URL("$BASE_URL/api/student/exam-sessions/$sessionId/$endpoint").openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = 10_000
                readTimeout = 10_000
                doOutput = true
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Accept", "application/json")
            }

            val body = JSONObject()
                .put("event_type", eventType)
                .put("message", message)
                .put("student_username", getSavedStudentName())
                .put("device_id", getStoredDeviceId())
                .put("device_name", getDeviceName())

            connection.outputStream.use { output ->
                output.write(body.toString().toByteArray())
            }

            val responseCode = connection.responseCode
            val responseBody = (if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream
            })?.bufferedReader()?.use { it.readText() }.orEmpty()

            val json = runCatching { JSONObject(responseBody) }.getOrNull()
            if (json?.optBoolean("force_exit", false) == true) {
                val serverMessage = json.optString("message", "Sesi ujian sudah dinonaktifkan oleh guru.")
                runOnUiThread {
                    forceExitFromServer(serverMessage)
                }
            }

            connection.disconnect()
        }
    }

    private fun forceExitFromServer(message: String) {
        if (isForceExiting) {
            return
        }

        isForceExiting = true
        stopHeartbeat()
        exitExamLockMode()
        clearActiveSession()
        activeSessionId = null
        examWebView?.destroy()
        examWebView = null

        runCatching {
            Snackbar.make(findViewById(android.R.id.content), message, Snackbar.LENGTH_LONG).show()
        }

        heartbeatHandler.postDelayed({
            finishAndRemoveTask()
        }, 800)
    }

    private fun reportStudentAction(eventType: String, message: String) {
        activeSessionId?.let { sessionId ->
            Thread {
                postSessionEvent(
                    sessionId = sessionId,
                    endpoint = "activity",
                    eventType = eventType,
                    message = message
                )
            }.start()
        }
    }

    private fun applySystemBarsPadding(rootId: Int) {
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(rootId)) { view: View, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            view.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }
    }

    private fun enterExamLockMode() {
        hideSystemBars()
        runCatching { startLockTask() }
    }

    private fun exitExamLockMode() {
        runCatching { stopLockTask() }
        showSystemBars()
    }

    private fun hideSystemBars() {
        window.insetsController?.apply {
            hide(WindowInsets.Type.systemBars())
            systemBarsBehavior = WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
        }
    }

    private fun showSystemBars() {
        window.insetsController?.show(WindowInsets.Type.systemBars())
    }

    private fun verifyExitPin(sessionId: Long, pin: String): ExitResult {
        return try {
            val connection = (URL("$BASE_URL/api/student/exam-sessions/$sessionId/exit").openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = 10_000
                readTimeout = 10_000
                doOutput = true
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Accept", "application/json")
            }

            connection.outputStream.use { output ->
                output.write(
                    JSONObject()
                        .put("pin", pin)
                        .put("student_username", getSavedStudentName())
                        .put("device_id", getStoredDeviceId())
                        .put("device_name", getDeviceName())
                        .toString()
                        .toByteArray()
                )
            }

            val responseCode = connection.responseCode
            val responseBody = (if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream
            })?.bufferedReader()?.use { it.readText() }.orEmpty()

            val json = JSONObject(responseBody)
            ExitResult(
                success = responseCode in 200..299,
                message = json.optString(
                    "message",
                    if (responseCode in 200..299) "PIN keluar benar." else "PIN keluar salah."
                )
            )
        } catch (_: Exception) {
            ExitResult(
                success = false,
                message = "Tidak bisa memverifikasi PIN keluar."
            )
        }
    }

    private data class ExitResult(
        val success: Boolean,
        val message: String
    )

    override fun onResume() {
        super.onResume()
        if (examWebView != null) {
            enterExamLockMode()
            startHeartbeat()
        }
    }

    override fun onStop() {
        super.onStop()
        activeSessionId?.let { sessionId ->
            Thread {
                postSessionEvent(
                    sessionId = sessionId,
                    endpoint = "activity",
                    eventType = "app_backgrounded",
                    message = "Aplikasi keluar dari layar utama atau proses ujian terganggu."
                )
            }.start()
        }
    }

    override fun onUserLeaveHint() {
        super.onUserLeaveHint()
        if (examWebView != null) {
            reportStudentAction("home_pressed", "Siswa menekan tombol Home/Recent atau mencoba meninggalkan aplikasi.")
        }
    }
}
