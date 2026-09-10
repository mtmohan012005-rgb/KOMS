package com.koms.app.ui.student

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.koms.app.data.model.AttendanceHistory
import com.koms.app.databinding.ItemAttendanceBinding

class AttendanceAdapter(private var list: List<AttendanceHistory>) : RecyclerView.Adapter<AttendanceAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemAttendanceBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemAttendanceBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = list[position]
        holder.binding.tvSessionDate.text = item.sessionDate
        holder.binding.tvRemarks.text = item.remarks ?: "Regular Session (${item.startTime ?: "18:00"})"
        holder.binding.tvStatus.text = item.status.uppercase()
    }

    override fun getItemCount() = list.size

    fun updateList(newList: List<AttendanceHistory>) {
        list = newList
        notifyDataSetChanged()
    }
}
