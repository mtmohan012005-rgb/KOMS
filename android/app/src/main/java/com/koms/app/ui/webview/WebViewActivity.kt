package com.koms.app.ui.webview

import android.annotation.SuppressLint
import android.app.AlertDialog
import android.content.Intent
import android.graphics.Bitmap
import android.net.Uri
import android.os.Bundle
import android.view.View
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.EditText
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import com.koms.app.databinding.ActivityWebviewBinding

class WebViewActivity : AppCompatActivity() {

    private lateinit var binding: ActivityWebviewBinding
    
    // Server URLs
    private val CLOUD_URL = "https://koms-backend.onrender.com/"
    private val LOCAL_URL = "http://10.0.2.2:8080/"
    private val LOCAL_DEV_URL = "http://localhost:8080/"
    
    private var currentUrl = CLOUD_URL
    private var fileUploadCallback: ValueCallback<Array<Uri>>? = null

    // Activity result launcher for file uploads (photos, documents, receipts)
    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (fileUploadCallback == null) return@registerForActivityResult

        val uris: Array<Uri>? = if (result.resultCode == RESULT_OK && result.data != null) {
            val data = result.data
            if (data?.clipData != null) {
                val count = data.clipData!!.itemCount
                Array(count) { i -> data.clipData!!.getItemAt(i).uri }
            } else if (data?.data != null) {
                arrayOf(data.data!!)
            } else {
                null
            }
        } else {
            null
        }

        fileUploadCallback?.onReceiveValue(uris)
        fileUploadCallback = null
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityWebviewBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupWebView()
        setupTopBarActions()
        setupBackNavigation()

        // Load initial portal page
        loadPortal(currentUrl)
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        val settings = binding.komsWebView.settings
        settings.javaScriptEnabled = true
        settings.domStorageEnabled = true
        settings.databaseEnabled = true
        settings.allowFileAccess = true
        settings.allowContentAccess = true
        settings.loadWithOverviewMode = true
        settings.useWideViewPort = true
        settings.setSupportZoom(true)
        settings.builtInZoomControls = false
        settings.displayZoomControls = false
        settings.cacheMode = WebSettings.LOAD_DEFAULT
        settings.mediaPlaybackRequiresUserGesture = false

        // Custom WebViewClient to stay inside app and catch errors
        binding.komsWebView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                val url = request?.url?.toString() ?: return false

                // External schemes (tel, mailto, whatsapp)
                if (url.startsWith("tel:") || url.startsWith("mailto:") || url.startsWith("whatsapp:")) {
                    try {
                        startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                        return true
                    } catch (e: Exception) {
                        return false
                    }
                }

                // Keep all web traffic inside our WebView
                return false
            }

            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                binding.webProgressBar.visibility = View.VISIBLE
                binding.layoutError.visibility = View.GONE
                updateUrlDisplay(url ?: currentUrl)
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                binding.webProgressBar.visibility = View.GONE
                binding.swipeRefreshLayout.isRefreshing = false
                updateUrlDisplay(url ?: currentUrl)
            }

            override fun onReceivedError(view: WebView?, request: WebResourceRequest?, error: WebResourceError?) {
                // Show error layout only for main frame errors
                if (request?.isForMainFrame == true) {
                    binding.swipeRefreshLayout.isRefreshing = false
                    binding.webProgressBar.visibility = View.GONE
                    binding.layoutError.visibility = View.VISIBLE
                    binding.tvErrorDetails.text = "Error connecting to server:\n${error?.description ?: "Network unreachable"}"
                }
            }
        }

        // Custom WebChromeClient for progress & file uploads
        binding.komsWebView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView?, newProgress: Int) {
                binding.webProgressBar.progress = newProgress
                if (newProgress >= 100) {
                    binding.webProgressBar.visibility = View.GONE
                } else {
                    binding.webProgressBar.visibility = View.VISIBLE
                }
            }

            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                fileUploadCallback?.onReceiveValue(null)
                fileUploadCallback = filePathCallback

                val intent = fileChooserParams?.createIntent() ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                    type = "*/*"
                    addCategory(Intent.CATEGORY_OPENABLE)
                }

                try {
                    fileChooserLauncher.launch(intent)
                } catch (e: Exception) {
                    fileUploadCallback = null
                    Toast.makeText(this@WebViewActivity, "Cannot open file picker", Toast.LENGTH_SHORT).show()
                    return false
                }
                return true
            }
        }

        // Swipe refresh listener
        binding.swipeRefreshLayout.setColorSchemeColors(0xFFDF241B.toInt(), 0xFFFFD21A.toInt())
        binding.swipeRefreshLayout.setOnRefreshListener {
            binding.komsWebView.reload()
        }

        // Retry button in error screen
        binding.btnRetry.setOnClickListener {
            binding.layoutError.visibility = View.GONE
            binding.komsWebView.reload()
        }

        binding.btnErrorSwitchServer.setOnClickListener {
            showServerSwitchDialog()
        }
    }

    private fun setupTopBarActions() {
        binding.btnWebHome.setOnClickListener {
            loadPortal(currentUrl)
        }

        binding.btnWebRefresh.setOnClickListener {
            binding.komsWebView.reload()
        }

        binding.btnSwitchServer.setOnClickListener {
            showServerSwitchDialog()
        }
    }

    private fun loadPortal(url: String) {
        currentUrl = url
        updateUrlDisplay(url)
        binding.komsWebView.loadUrl(url)
    }

    private fun updateUrlDisplay(url: String) {
        val display = when {
            url.contains("onrender.com") -> "Cloud (Render) • " + Uri.parse(url).path
            url.contains("10.0.2.2") -> "Local Emulator (8080)"
            url.contains("localhost") || url.contains("127.0.0.1") -> "Local Dev (8080)"
            else -> Uri.parse(url).host ?: url
        }
        binding.tvServerUrl.text = display
    }

    private fun showServerSwitchDialog() {
        val servers = arrayOf(
            "Cloud Production (Render.com)",
            "Local Dev (http://localhost:8080/)",
            "Android Emulator (http://10.0.2.2:8080/)",
            "Custom URL..."
        )

        AlertDialog.Builder(this)
            .setTitle("Select KOMS Server")
            .setItems(servers) { _, which ->
                when (which) {
                    0 -> loadPortal(CLOUD_URL)
                    1 -> loadPortal(LOCAL_DEV_URL)
                    2 -> loadPortal(LOCAL_URL)
                    3 -> showCustomUrlDialog()
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showCustomUrlDialog() {
        val input = EditText(this).apply {
            hint = "http://192.168.1.xxx:8080/"
            setText(currentUrl)
        }

        AlertDialog.Builder(this)
            .setTitle("Enter KOMS Server URL")
            .setView(input)
            .setPositiveButton("Connect") { _, _ ->
                val entered = input.text.toString().trim()
                if (entered.isNotEmpty()) {
                    loadPortal(if (entered.startsWith("http")) entered else "http://$entered")
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun setupBackNavigation() {
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (binding.komsWebView.canGoBack()) {
                    binding.komsWebView.goBack()
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    override fun onDestroy() {
        binding.komsWebView.destroy()
        super.onDestroy()
    }
}
