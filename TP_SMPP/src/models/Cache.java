/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package models;

/**
 *
 * @author Roger MUSHAGALUSA
 */
public class Cache {
    String etat;
    String data;

    public Cache() {
    }

    public Cache(String etat, String data) {
        this.etat = etat;
        this.data = data;
    }
    
    public Cache(String data) {
        this.data = data;
    }

    public String getEtat() {
        return etat;
    }

    public void setEtat(String etat) {
        this.etat = etat;
    }

    public String getData() {
        return data;
    }

    public void setData(String data) {
        this.data = data;
    }    
}
